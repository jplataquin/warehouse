<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LoggerAssignmentController;
use App\Http\Controllers\MqmsComponentImportController;
use App\Http\Controllers\MqmsProjectImportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectImportController;
use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TestMqmsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-mqms-projects', [TestMqmsController::class, 'projects']);

Route::get('/public/dashboard/{token}', [PublicDashboardController::class, 'show'])->name('public.warehouse.dashboard');
Route::get('/public/items/{item}/stock', [PublicDashboardController::class, 'getStock'])->name('public.items.stock');

Auth::routes(['register' => false, 'reset' => false]);

Route::middleware(['auth'])->group(function () {
    Route::get('/password/change', [ChangePasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [ChangePasswordController::class, 'updatePassword'])->name('password.change.update');

    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/search', [SearchController::class, 'index'])->name('global.search');
    Route::get('/warehouses/{warehouse}/dashboard', [DashboardController::class, 'warehouseDashboard'])
        ->name('logger.warehouse.dashboard')
        ->middleware('logger');
    Route::get('/logger/rules', [DashboardController::class, 'loggerRules'])
        ->name('logger.rules')
        ->middleware('logger');

    Route::get('items/assets', [ItemController::class, 'assets'])->name('items.assets');
    Route::patch('items/{item}/status', [ItemController::class, 'updateStatus'])->name('items.update-status');
    Route::get('items/{item}/stock', [ItemController::class, 'getStock'])->name('items.stock');

    // Shared routes (mostly read-only for loggers, full for others)
    Route::get('ledgers/allocations-by-warehouse', [LedgerController::class, 'getAllocationsByWarehouse'])->name('ledgers.allocations_by_warehouse');
    Route::get('ledgers/warehouse/{warehouse}/item/{item}', [LedgerController::class, 'itemHistory'])->name('ledgers.item_history');
    Route::get('ledgers/warehouse/{warehouse}/item/{item}/print', [LedgerController::class, 'printItemHistory'])->name('ledgers.item_history.print');
    Route::resource('ledgers', LedgerController::class);

    // Supervisor + Admin routes
    Route::middleware(['supervisor'])->group(function () {
        // Bulk Project Import
        Route::get('projects/bulk-import', [ProjectImportController::class, 'showUploadForm'])->name('projects.import.form');
        Route::get('projects/bulk-import/template', [ProjectImportController::class, 'downloadTemplate'])->name('projects.import.template');
        Route::post('projects/bulk-import/preview', [ProjectImportController::class, 'preview'])->name('projects.import.preview');
        Route::post('projects/bulk-import/store', [ProjectImportController::class, 'store'])->name('projects.import.store');

        // MQMS Project Import
        Route::get('projects/mqms-import/preview', [MqmsProjectImportController::class, 'preview'])->name('projects.mqms-import.preview');
        Route::post('projects/mqms-import/store', [MqmsProjectImportController::class, 'store'])->name('projects.mqms-import.store');

        Route::resource('projects', ProjectController::class);
        Route::post('warehouses/{warehouse}/loggers', [WarehouseController::class, 'assignLogger'])->name('warehouses.loggers.assign');
        Route::delete('warehouses/{warehouse}/loggers/{logger}', [WarehouseController::class, 'removeLogger'])->name('warehouses.loggers.remove');
        Route::post('warehouses/{warehouse}/public-token/generate', [WarehouseController::class, 'generatePublicToken'])->name('warehouses.public_token.generate');
        Route::post('warehouses/{warehouse}/public-token/revoke', [WarehouseController::class, 'revokePublicToken'])->name('warehouses.public_token.revoke');

        // Bulk Warehouse Import
        Route::get('warehouses/bulk-import', [WarehouseImportController::class, 'showUploadForm'])->name('warehouses.import.form');
        Route::get('warehouses/bulk-import/template', [WarehouseImportController::class, 'downloadTemplate'])->name('warehouses.import.template');
        Route::post('warehouses/bulk-import/preview', [WarehouseImportController::class, 'preview'])->name('warehouses.import.preview');
        Route::post('warehouses/bulk-import/store', [WarehouseImportController::class, 'store'])->name('warehouses.import.store');

        Route::resource('warehouses', WarehouseController::class);

        // MQMS Component Import to Warehouse
        Route::get('warehouses/{warehouse}/import-components/sections', [MqmsComponentImportController::class, 'sections'])->name('warehouses.import-components.sections');
        Route::get('warehouses/{warehouse}/import-components/preview', [MqmsComponentImportController::class, 'preview'])->name('warehouses.import-components.preview');
        Route::post('warehouses/{warehouse}/import-components/store', [MqmsComponentImportController::class, 'store'])->name('warehouses.import-components.store');

        // Bulk Item Import (Must be above resource to avoid being caught by {item})
        Route::get('items/bulk-import', [ItemImportController::class, 'showUploadForm'])->name('items.import.form');
        Route::get('items/bulk-import/template', [ItemImportController::class, 'downloadTemplate'])->name('items.import.template');
        Route::post('items/bulk-import/preview', [ItemImportController::class, 'preview'])->name('items.import.preview');
        Route::post('items/bulk-import/store', [ItemImportController::class, 'store'])->name('items.import.store');

        Route::resource('items', ItemController::class);
        Route::resource('allocations', AllocationController::class)->except(['index', 'create']);

        Route::get('assignments', [LoggerAssignmentController::class, 'index'])->name('assignments.index');
        Route::post('assignments', [LoggerAssignmentController::class, 'store'])->name('assignments.store');
    });

    // Admin only routes
    Route::middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('ledgers/{ledger}/approve', [LedgerController::class, 'approve'])->name('ledgers.approve');
        Route::get('items/{item}/merge', [ItemController::class, 'mergeForm'])->name('items.merge.form');
        Route::post('items/{item}/merge', [ItemController::class, 'merge'])->name('items.merge');
    });
});
