<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CloneProductionCommandTest extends TestCase
{
    protected string $sourceDbFile;
    protected string $targetDbFile;
    protected string $sourceStorageDir;
    protected string $targetStorageDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDbFile = database_path('test_source_db.sqlite');
        $this->targetDbFile = database_path('test_target_db.sqlite');

        if (file_exists($this->sourceDbFile)) {
            unlink($this->sourceDbFile);
        }
        if (file_exists($this->targetDbFile)) {
            unlink($this->targetDbFile);
        }

        touch($this->sourceDbFile);
        touch($this->targetDbFile);

        $this->sourceStorageDir = storage_path('test_source_storage');
        $this->targetStorageDir = storage_path('test_target_storage');

        if (is_dir($this->sourceStorageDir)) {
            $this->cleanDir($this->sourceStorageDir);
            rmdir($this->sourceStorageDir);
        }
        if (is_dir($this->targetStorageDir)) {
            $this->cleanDir($this->targetStorageDir);
            rmdir($this->targetStorageDir);
        }

        mkdir($this->sourceStorageDir, 0755, true);
        mkdir($this->targetStorageDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->sourceDbFile)) {
            unlink($this->sourceDbFile);
        }
        if (file_exists($this->targetDbFile)) {
            unlink($this->targetDbFile);
        }

        if (is_dir($this->sourceStorageDir)) {
            $this->cleanDir($this->sourceStorageDir);
            rmdir($this->sourceStorageDir);
        }
        if (is_dir($this->targetStorageDir)) {
            $this->cleanDir($this->targetStorageDir);
            rmdir($this->targetStorageDir);
        }

        parent::tearDown();
    }

    private function cleanDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }
    }

    public function test_cannot_run_outside_of_local_environment()
    {
        // By default, the environment in tests is 'testing', so this should fail the 'local' check
        $this->artisan('db:clone-production', [
            '--prod-db' => $this->sourceDbFile,
            '--force' => true,
        ])
            ->expectsOutput('This command can only be run in the local environment to protect production data.')
            ->assertExitCode(1);
    }

    public function test_it_truncates_and_clones_rows_to_target_leaving_migrations_untouched()
    {
        // Mock environment as local
        $this->app['env'] = 'local';

        // 1. Setup Source and Target Connections
        config(['database.connections.source_sqlite_test' => [
            'driver' => 'sqlite',
            'database' => $this->sourceDbFile,
            'prefix' => '',
        ]]);

        config(['database.connections.target_sqlite_test' => [
            'driver' => 'sqlite',
            'database' => $this->targetDbFile,
            'prefix' => '',
        ]]);

        // 2. Create tables on BOTH Source and Target (since we only truncate and copy rows)
        foreach (['source_sqlite_test', 'target_sqlite_test'] as $conn) {
            Schema::connection($conn)->create('users_temp', function ($table) {
                $table->id();
                $table->string('name');
            });

            Schema::connection($conn)->create('migrations', function ($table) {
                $table->string('migration');
                $table->integer('batch');
            });
        }

        // 3. Populate Target with "old" data
        DB::connection('target_sqlite_test')->table('users_temp')->insert([
            ['name' => 'Old Developer'],
        ]);
        DB::connection('target_sqlite_test')->table('migrations')->insert([
            ['migration' => '0001_01_01_000000_create_users_table', 'batch' => 1],
        ]);

        // 4. Populate Source with "production/new" data
        DB::connection('source_sqlite_test')->table('users_temp')->insert([
            ['name' => 'John Doe'],
            ['name' => 'Jane Doe'],
        ]);
        DB::connection('source_sqlite_test')->table('migrations')->insert([
            ['migration' => '2026_05_21_171622_add_role_to_users_table', 'batch' => 2],
        ]);

        // 5. Setup storage files
        file_put_contents($this->sourceStorageDir . '/test.txt', 'source-file-content');
        mkdir($this->sourceStorageDir . '/subfolder');
        file_put_contents($this->sourceStorageDir . '/subfolder/nested.txt', 'nested-content');

        file_put_contents($this->targetStorageDir . '/old.txt', 'old-file-content');

        // 6. Run Command
        $this->artisan('db:clone-production', [
            '--prod-conn' => 'sqlite',
            '--prod-db' => $this->sourceDbFile,
            '--prod-storage' => $this->sourceStorageDir,
            '--target-conn' => 'target_sqlite_test',
            '--target-db' => $this->targetDbFile,
            '--target-storage' => $this->targetStorageDir,
            '--force' => true,
        ])->assertExitCode(0);

        // 7. Assert target users_temp was truncated and production rows were copied
        $targetUsers = DB::connection('target_sqlite_test')->table('users_temp')->get();
        $this->assertCount(2, $targetUsers);
        $this->assertEquals('John Doe', $targetUsers[0]->name);
        $this->assertEquals('Jane Doe', $targetUsers[1]->name);

        // 8. Assert target migrations table remains completely untouched
        $targetMigrations = DB::connection('target_sqlite_test')->table('migrations')->get();
        $this->assertCount(1, $targetMigrations);
        $this->assertEquals('0001_01_01_000000_create_users_table', $targetMigrations[0]->migration);

        // 9. Assert Storage is Cloned
        $this->assertTrue(file_exists($this->targetStorageDir . '/test.txt'));
        $this->assertEquals('source-file-content', file_get_contents($this->targetStorageDir . '/test.txt'));
        $this->assertTrue(file_exists($this->targetStorageDir . '/subfolder/nested.txt'));
        $this->assertEquals('nested-content', file_get_contents($this->targetStorageDir . '/subfolder/nested.txt'));
        $this->assertFalse(file_exists($this->targetStorageDir . '/old.txt'));
    }

    public function test_it_warns_when_table_is_missing_from_target_database()
    {
        // Mock environment as local
        $this->app['env'] = 'local';

        // Setup Source and Target Connections
        config(['database.connections.source_sqlite_test' => [
            'driver' => 'sqlite',
            'database' => $this->sourceDbFile,
            'prefix' => '',
        ]]);

        config(['database.connections.target_sqlite_test' => [
            'driver' => 'sqlite',
            'database' => $this->targetDbFile,
            'prefix' => '',
        ]]);

        // Create table ONLY on Source, NOT on Target
        Schema::connection('source_sqlite_test')->create('missing_on_target', function ($table) {
            $table->id();
            $table->string('title');
        });

        DB::connection('source_sqlite_test')->table('missing_on_target')->insert([
            ['title' => 'Some Production Item'],
        ]);

        // Run Command
        $this->artisan('db:clone-production', [
            '--prod-conn' => 'sqlite',
            '--prod-db' => $this->sourceDbFile,
            '--target-conn' => 'target_sqlite_test',
            '--target-db' => $this->targetDbFile,
            '--force' => true,
        ])
            ->expectsOutput("Table 'missing_on_target' is missing from the target database. Skipping.")
            ->assertExitCode(0);
    }
}
