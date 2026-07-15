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

    public function test_it_clones_sqlite_database_via_direct_file_copy()
    {
        // Mock environment as local
        $this->app['env'] = 'local';

        // 1. Setup Source Connection and Table
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

        Schema::connection('source_sqlite_test')->create('users_temp', function ($table) {
            $table->id();
            $table->string('name');
        });

        DB::connection('source_sqlite_test')->table('users_temp')->insert([
            ['name' => 'John Doe'],
            ['name' => 'Jane Doe'],
        ]);

        // 2. Setup storage files
        file_put_contents($this->sourceStorageDir . '/test.txt', 'source-file-content');
        mkdir($this->sourceStorageDir . '/subfolder');
        file_put_contents($this->sourceStorageDir . '/subfolder/nested.txt', 'nested-content');

        file_put_contents($this->targetStorageDir . '/old.txt', 'old-file-content');

        // 3. Run Command
        $this->artisan('db:clone-production', [
            '--prod-conn' => 'sqlite',
            '--prod-db' => $this->sourceDbFile,
            '--prod-storage' => $this->sourceStorageDir,
            '--target-conn' => 'target_sqlite_test',
            '--target-db' => $this->targetDbFile,
            '--target-storage' => $this->targetStorageDir,
            '--force' => true,
        ])->assertExitCode(0);

        // 4. Assert Database is Cloned
        $targetRows = DB::connection('target_sqlite_test')->table('users_temp')->get();
        $this->assertCount(2, $targetRows);
        $this->assertEquals('John Doe', $targetRows[0]->name);
        $this->assertEquals('Jane Doe', $targetRows[1]->name);

        // 5. Assert Storage is Cloned
        $this->assertTrue(file_exists($this->targetStorageDir . '/test.txt'));
        $this->assertEquals('source-file-content', file_get_contents($this->targetStorageDir . '/test.txt'));
        $this->assertTrue(file_exists($this->targetStorageDir . '/subfolder/nested.txt'));
        $this->assertEquals('nested-content', file_get_contents($this->targetStorageDir . '/subfolder/nested.txt'));
        $this->assertFalse(file_exists($this->targetStorageDir . '/old.txt'));
    }

    public function test_it_clones_database_table_by_table()
    {
        // Mock environment as local
        $this->app['env'] = 'local';

        // To force table-by-table copy (Strategy 2/3), we configure different connection drivers,
        // or trigger the table-by-table branch.
        // We can force it by having different drivers or by setting one connection database to :memory:,
        // which prevents Strategy 1 from executing file copy and defaults to Strategy 2/3 table-by-table copy.

        // Setup Source SQLite file
        config(['database.connections.source_sqlite_test' => [
            'driver' => 'sqlite',
            'database' => $this->sourceDbFile,
            'prefix' => '',
        ]]);

        // Target connection is an in-memory database
        config(['database.connections.target_memory_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Create table in source SQLite database
        Schema::connection('source_sqlite_test')->create('items_temp', function ($table) {
            $table->id();
            $table->string('title');
        });

        DB::connection('source_sqlite_test')->table('items_temp')->insert([
            ['title' => 'Item A'],
            ['title' => 'Item B'],
        ]);

        // Create an ignored table in source SQLite database
        Schema::connection('source_sqlite_test')->create('cache', function ($table) {
            $table->string('key')->unique();
            $table->text('value');
        });

        DB::connection('source_sqlite_test')->table('cache')->insert([
            ['key' => 'test_key', 'value' => 'test_value'],
        ]);

        // Run command targeting the in-memory SQLite connection
        $this->artisan('db:clone-production', [
            '--prod-conn' => 'sqlite',
            '--prod-db' => $this->sourceDbFile,
            '--target-conn' => 'target_memory_test',
            '--target-db' => ':memory:',
            '--force' => true,
        ])->assertExitCode(0);

        // Assert table-by-table successfully copied to target (which is in-memory target_memory_test connection)
        $targetRows = DB::connection('target_memory_test')->table('items_temp')->get();
        $this->assertCount(2, $targetRows);
        $this->assertEquals('Item A', $targetRows[0]->title);
        $this->assertEquals('Item B', $targetRows[1]->title);

        // Assert ignored tables (like cache) are NOT copied/created on target connection
        $this->assertFalse(Schema::connection('target_memory_test')->hasTable('cache'));
    }
}
