<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

#[Signature('db:clone-production 
    {--prod-conn= : The production database connection driver}
    {--prod-host= : The production database host}
    {--prod-port= : The production database port}
    {--prod-db= : The production database name or sqlite path}
    {--prod-user= : The production database username}
    {--prod-pass= : The production database password}
    {--prod-storage= : The path to the production storage directory}
    {--target-conn= : Override the target database connection}
    {--target-db= : Override the target database name or sqlite path}
    {--target-storage= : Override the target storage directory path}
    {--force : Force the operation without confirmation}
')]
#[Description('Truncate target database and local storage, and clone production database and storage files.')]
class CloneProduction extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Force check for local environment
        if (!app()->environment('local')) {
            $this->error('This command can only be run in the local environment to protect production data.');
            return 1;
        }

        // 2. Set up connections and storage paths
        try {
            $sourceConnection = $this->setupProductionConnection();
            $targetConnection = $this->setupTargetConnection();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $prodStorage = $this->option('prod-storage') ?: env('PROD_STORAGE_PATH');
        $targetStorage = $this->option('target-storage') ?: storage_path('app');

        $targetConfig = config("database.connections.{$targetConnection}");
        if (($targetConfig['driver'] ?? '') === 'sqlite' && ($targetConfig['database'] ?? '') === ':memory:') {
            $this->warn('Warning: The target database is configured as ":memory:". Cloned data will be lost as soon as this command finishes.');
        }

        // 3. Retrieve and display production tables to be cloned
        try {
            $sourceTables = $this->getSourceTables($sourceConnection);
        } catch (\Exception $e) {
            $this->error('Failed to retrieve list of production tables: ' . $e->getMessage());
            return 1;
        }

        $sourceConfig = config("database.connections.{$sourceConnection}");
        $sourceDbName = $sourceConfig['database'] ?? 'unknown';
        $sourceDriverName = $sourceConfig['driver'] ?? 'unknown';

        $targetDbName = $targetConfig['database'] ?? 'unknown';
        $targetDriverName = $targetConfig['driver'] ?? 'unknown';

        $this->info("Source Database: {$sourceDbName} (driver: {$sourceDriverName})");
        $this->info("Target Database: {$targetDbName} (driver: {$targetDriverName})");
        $this->line('');

        $this->info('The following production tables will be cloned:');
        if (empty($sourceTables)) {
            $this->warn('  (No tables found in production database)');
        } else {
            foreach ($sourceTables as $table) {
                $this->line("  - {$table}");
            }
        }
        $this->line('');

        // 4. Confirm operation
        if (!$this->option('force')) {
            $confirmed = $this->confirm(
                "This will TRUNCATE the current database on connection '{$targetConnection}' and DELETE target storage files at '{$targetStorage}'. Are you sure you want to clone production?",
                false
            );

            if (!$confirmed) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // 5. Clone Database
        $this->info('Cloning production database...');
        $dbCloned = $this->cloneDatabase($sourceConnection, $targetConnection, $sourceTables);
        if (!$dbCloned) {
            $this->error('Failed to clone database.');
            return 1;
        }

        // 5. Clone Storage Files
        if (empty($prodStorage)) {
            $this->warn('Production storage path is not configured (PROD_STORAGE_PATH or --prod-storage). Skipping storage clone.');
        } else {
            if (!is_dir($prodStorage)) {
                $this->error("Production storage directory not found at: {$prodStorage}");
                return 1;
            }

            $this->info("Truncating current storage directory: {$targetStorage}...");
            $this->cleanDirectory($targetStorage);

            $this->info("Copying storage files from {$prodStorage} to {$targetStorage}...");
            $this->recursiveCopy($prodStorage, $targetStorage);
            $this->info('Storage files cloned successfully.');
        }

        $this->info('Production system cloned successfully.');
        return 0;
    }

    /**
     * Set up dynamic source production connection configuration.
     */
    protected function setupProductionConnection(): string
    {
        $driver = $this->option('prod-conn') ?: env('PROD_DB_CONNECTION', env('PROD_DB_DRIVER', 'mysql'));
        $host = $this->option('prod-host') ?: env('PROD_DB_HOST', '127.0.0.1');
        $port = $this->option('prod-port') ?: env('PROD_DB_PORT', '3306');
        $database = $this->option('prod-db') ?: env('PROD_DB_DATABASE');
        $username = $this->option('prod-user') ?: env('PROD_DB_USERNAME', 'root');
        $password = $this->option('prod-pass') ?: env('PROD_DB_PASSWORD', '');

        if (empty($database)) {
            throw new \Exception('Production database is not configured. Please define PROD_DB_DATABASE or use --prod-db.');
        }

        $connectionName = 'production_source_dynamic';

        $config = [
            'driver' => $driver,
            'prefix' => '',
        ];

        if ($driver === 'sqlite') {
            $config['database'] = $database;
            $config['foreign_key_constraints'] = env('DB_FOREIGN_KEYS', true);
        } else {
            $config['host'] = $host;
            $config['port'] = $port;
            $config['database'] = $database;
            $config['username'] = $username;
            $config['password'] = $password;
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $config['charset'] = 'utf8mb4';
                $config['collation'] = 'utf8mb4_unicode_ci';
            }
        }

        config(["database.connections.{$connectionName}" => $config]);

        return $connectionName;
    }

    /**
     * Set up and return the target connection name.
     */
    protected function setupTargetConnection(): string
    {
        $connectionName = $this->option('target-conn') ?: config('database.default');

        $targetDb = $this->option('target-db');
        if ($targetDb) {
            config(["database.connections.{$connectionName}.database" => $targetDb]);
        }

        return $connectionName;
    }

    /**
     * Execute database cloning logic.
     */
    protected function cloneDatabase(string $sourceConnection, string $targetConnection, ?array $sourceTables = null): bool
    {
        $sourceDriver = config("database.connections.{$sourceConnection}.driver");
        $targetDriver = config("database.connections.{$targetConnection}.driver");

        // Strategy 1: SQLite to SQLite (Direct File Copy)
        if ($sourceDriver === 'sqlite' && $targetDriver === 'sqlite') {
            $sourceDb = config("database.connections.{$sourceConnection}.database");
            $targetDb = config("database.connections.{$targetConnection}.database");

            if ($sourceDb !== ':memory:' && $targetDb !== ':memory:') {
                $this->info('Cloning SQLite database via file copy...');
                if (!file_exists($sourceDb)) {
                    $this->error("Source SQLite database file not found at: {$sourceDb}");
                    return false;
                }

                @mkdir(dirname($targetDb), 0755, true);

                if (copy($sourceDb, $targetDb)) {
                    $this->info('Database cloned successfully via file copy.');
                    return true;
                } else {
                    $this->error('Failed to copy SQLite database file.');
                    return false;
                }
            }
        }

        // Strategy 2/3: Table-by-table schema & data copy
        $this->info('Cloning database table-by-table...');

        // Retrieve list of tables from source if not already provided
        if ($sourceTables === null) {
            $sourceTables = $this->getSourceTables($sourceConnection);
        }

        // Retrieve and drop all tables on the target first (clean/truncate)
        $targetTablesInfo = Schema::connection($targetConnection)->getTables();
        $targetTables = [];
        foreach ($targetTablesInfo as $tableInfo) {
            $targetTables[] = is_array($tableInfo) ? ($tableInfo['name'] ?? reset($tableInfo)) : (is_object($tableInfo) ? ($tableInfo->name ?? $tableInfo->Name) : $tableInfo);
        }

        Schema::connection($targetConnection)->disableForeignKeyConstraints();

        foreach ($targetTables as $table) {
            Schema::connection($targetConnection)->dropIfExists($table);
        }

        $sameDriver = ($sourceDriver === $targetDriver);

        if ($sameDriver && ($sourceDriver === 'mysql' || $sourceDriver === 'sqlite')) {
            // Recreate structures for exact schema copy
            foreach ($sourceTables as $table) {
                // Skip sqlite internal tables
                if ($sourceDriver === 'sqlite' && in_array($table, ['sqlite_sequence', 'sqlite_stat1', 'sqlite_stat2', 'sqlite_stat3', 'sqlite_stat4'])) {
                    continue;
                }

                $this->line("Recreating structure for table: {$table}");
                
                try {
                    $createSql = null;

                    if ($sourceDriver === 'mysql') {
                        $result = DB::connection($sourceConnection)->select("SHOW CREATE TABLE `{$table}`");
                        if (!empty($result)) {
                            $row = (array) $result[0];
                            $createSql = $row['Create Table'] ?? $row['create table'] ?? null;
                        }
                    } elseif ($sourceDriver === 'sqlite') {
                        $result = DB::connection($sourceConnection)->select("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                        if (!empty($result)) {
                            $row = (array) $result[0];
                            $createSql = $row['sql'] ?? null;
                        }
                    }

                    if ($createSql) {
                        DB::connection($targetConnection)->statement($createSql);
                    }
                } catch (\Throwable $e) {
                    $this->warn("  -> Skipping table structure copy for '{$table}' due to error: " . $e->getMessage());
                }
            }
        } else {
            // Cross-driver or other drivers: Use Laravel migrations to construct schema
            $this->info('Running migrations on target connection to construct schema...');
            Artisan::call('migrate:fresh', [
                '--database' => $targetConnection,
                '--force' => true,
            ]);
        }

        // Verify and get final target tables list
        $newTargetTablesInfo = Schema::connection($targetConnection)->getTables();
        $newTargetTables = [];
        foreach ($newTargetTablesInfo as $tableInfo) {
            $newTargetTables[] = is_array($tableInfo) ? ($tableInfo['name'] ?? reset($tableInfo)) : (is_object($tableInfo) ? ($tableInfo->name ?? $tableInfo->Name) : $tableInfo);
        }

        // Re-disable foreign key constraints (as migrate:fresh might reset them)
        Schema::connection($targetConnection)->disableForeignKeyConstraints();

        // Copy records
        foreach ($sourceTables as $table) {
            if (!in_array($table, $newTargetTables)) {
                $this->warn("Table '{$table}' is missing from the target schema. Skipping data copy.");
                continue;
            }

            $this->line("Copying data for table: {$table}");

            try {
                // Ensure table is clean on target
                DB::connection($targetConnection)->table($table)->truncate();

                $offset = 0;
                $limit = 1000;
                $totalCopied = 0;

                while (true) {
                    $rows = DB::connection($sourceConnection)->table($table)->offset($offset)->limit($limit)->get();
                    if ($rows->isEmpty()) {
                        break;
                    }

                    $insertData = [];
                    foreach ($rows as $row) {
                        $insertData[] = (array) $row;
                    }

                    DB::connection($targetConnection)->table($table)->insert($insertData);
                    $totalCopied += count($insertData);
                    $offset += $limit;
                }

                $this->line("  -> Copied {$totalCopied} rows.");
            } catch (\Throwable $e) {
                $this->warn("  -> Skipping data copy for '{$table}' due to error: " . $e->getMessage());
            }
        }

        Schema::connection($targetConnection)->enableForeignKeyConstraints();

        return true;
    }

    /**
     * Recursively delete directories and files except .gitignore.
     */
    protected function cleanDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->getFilename() === '.gitignore') {
                continue;
            }

            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
            }
        }
    }

    /**
     * Recursively copy files and directories.
     */
    protected function recursiveCopy(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }

        @mkdir($dst, 0755, true);
        $dir = opendir($src);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcFile = $src . DIRECTORY_SEPARATOR . $file;
            $dstFile = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcFile)) {
                $this->recursiveCopy($srcFile, $dstFile);
            } else {
                @copy($srcFile, $dstFile);
            }
        }

        closedir($dir);
    }

    /**
     * Retrieve list of tables from the source connection, isolated to the configured database.
     */
    protected function getSourceTables(string $sourceConnection): array
    {
        $driver = config("database.connections.{$sourceConnection}.driver");
        $databaseName = config("database.connections.{$sourceConnection}.database");
        $tables = [];

        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                $results = DB::connection($sourceConnection)->select(
                    "SELECT table_name AS name FROM information_schema.tables WHERE table_schema = ? AND table_type IN ('BASE TABLE', 'VIEW')",
                    [$databaseName]
                );
                foreach ($results as $row) {
                    $row = (array) $row;
                    $tables[] = $row['name'] ?? $row['NAME'] ?? null;
                }
                $tables = array_filter($tables);
            } catch (\Exception $e) {
                // Fallback to standard schema if custom query fails
            }
        }

        if (empty($tables)) {
            $tablesInfo = Schema::connection($sourceConnection)->getTables();
            foreach ($tablesInfo as $tableInfo) {
                $tableName = null;
                $tableSchema = null;

                if (is_array($tableInfo)) {
                    $tableName = $tableInfo['name'] ?? reset($tableInfo);
                    $tableSchema = $tableInfo['schema'] ?? null;
                } elseif (is_object($tableInfo)) {
                    $tableName = $tableInfo->name ?? $tableInfo->Name ?? (string)$tableInfo;
                    $tableSchema = $tableInfo->schema ?? $tableInfo->Schema ?? null;
                } else {
                    $tableName = (string)$tableInfo;
                }

                // Isolate to the configured database name if schema details are available (skip for SQLite)
                if ($driver !== 'sqlite' && $tableSchema !== null && strtolower($tableSchema) !== strtolower($databaseName)) {
                    continue;
                }

                if ($tableName) {
                    $tables[] = $tableName;
                }
            }
        }

        return array_values(array_unique($tables));
    }
}
