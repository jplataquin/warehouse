<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AllocationController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoggerAssignmentController;
use App\Http\Controllers\TestMqmsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-mqms-projects', [TestMqmsController::class, 'projects']);

Auth::routes(['register' => false]);

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('global.search');
    Route::get('/warehouses/{warehouse}/dashboard', [DashboardController::class, 'warehouseDashboard'])
        ->name('logger.warehouse.dashboard')
        ->middleware('logger');

    Route::get('items/{item}/stock', [ItemController::class, 'getStock'])->name('items.stock');

    // Shared routes (mostly read-only for loggers, full for others)
    Route::get('ledgers/allocations-by-warehouse', [LedgerController::class, 'getAllocationsByWarehouse'])->name('ledgers.allocations_by_warehouse');
    Route::get('ledgers/warehouse/{warehouse}/item/{item}', [LedgerController::class, 'itemHistory'])->name('ledgers.item_history');
    Route::get('ledgers/warehouse/{warehouse}/item/{item}/print', [LedgerController::class, 'printItemHistory'])->name('ledgers.item_history.print');
    Route::resource('ledgers', LedgerController::class);

    // Supervisor + Admin routes
    Route::middleware(['supervisor'])->group(function () {
        // Bulk Project Import
        Route::get('projects/bulk-import', [\App\Http\Controllers\ProjectImportController::class, 'showUploadForm'])->name('projects.import.form');
        Route::get('projects/bulk-import/template', [\App\Http\Controllers\ProjectImportController::class, 'downloadTemplate'])->name('projects.import.template');
        Route::post('projects/bulk-import/preview', [\App\Http\Controllers\ProjectImportController::class, 'preview'])->name('projects.import.preview');
        Route::post('projects/bulk-import/store', [\App\Http\Controllers\ProjectImportController::class, 'store'])->name('projects.import.store');

        // MQMS Project Import
        Route::get('projects/mqms-import/preview', [\App\Http\Controllers\MqmsProjectImportController::class, 'preview'])->name('projects.mqms-import.preview');
        Route::post('projects/mqms-import/store', [\App\Http\Controllers\MqmsProjectImportController::class, 'store'])->name('projects.mqms-import.store');

        Route::resource('projects', ProjectController::class);
        Route::post('warehouses/{warehouse}/loggers', [WarehouseController::class, 'assignLogger'])->name('warehouses.loggers.assign');
        Route::delete('warehouses/{warehouse}/loggers/{logger}', [WarehouseController::class, 'removeLogger'])->name('warehouses.loggers.remove');

        // Bulk Warehouse Import
        Route::get('warehouses/bulk-import', [\App\Http\Controllers\WarehouseImportController::class, 'showUploadForm'])->name('warehouses.import.form');
        Route::get('warehouses/bulk-import/template', [\App\Http\Controllers\WarehouseImportController::class, 'downloadTemplate'])->name('warehouses.import.template');
        Route::post('warehouses/bulk-import/preview', [\App\Http\Controllers\WarehouseImportController::class, 'preview'])->name('warehouses.import.preview');
        Route::post('warehouses/bulk-import/store', [\App\Http\Controllers\WarehouseImportController::class, 'store'])->name('warehouses.import.store');

        Route::resource('warehouses', WarehouseController::class);

        // MQMS Component Import to Warehouse
        Route::get('warehouses/{warehouse}/import-components/sections', [\App\Http\Controllers\MqmsComponentImportController::class, 'sections'])->name('warehouses.import-components.sections');
        Route::get('warehouses/{warehouse}/import-components/preview', [\App\Http\Controllers\MqmsComponentImportController::class, 'preview'])->name('warehouses.import-components.preview');
        Route::post('warehouses/{warehouse}/import-components/store', [\App\Http\Controllers\MqmsComponentImportController::class, 'store'])->name('warehouses.import-components.store');

        // Bulk Item Import (Must be above resource to avoid being caught by {item})
        Route::get('items/bulk-import', [\App\Http\Controllers\ItemImportController::class, 'showUploadForm'])->name('items.import.form');
        Route::get('items/bulk-import/template', [\App\Http\Controllers\ItemImportController::class, 'downloadTemplate'])->name('items.import.template');
        Route::post('items/bulk-import/preview', [\App\Http\Controllers\ItemImportController::class, 'preview'])->name('items.import.preview');
        Route::post('items/bulk-import/store', [\App\Http\Controllers\ItemImportController::class, 'store'])->name('items.import.store');

        Route::get('items/assets', [ItemController::class, 'assets'])->name('items.assets');
        Route::resource('items', ItemController::class);
        Route::resource('allocations', AllocationController::class)->except(['index', 'create']);
        
        Route::get('assignments', [LoggerAssignmentController::class, 'index'])->name('assignments.index');
        Route::post('assignments', [LoggerAssignmentController::class, 'store'])->name('assignments.store');
    });

    // Admin only routes
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('ledgers/{ledger}/approve', [LedgerController::class, 'approve'])->name('ledgers.approve');
    });
});
