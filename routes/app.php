<?php

use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Fuel\FuelDeliveryController;
use App\Http\Controllers\Fuel\FuelTypeController;
use App\Http\Controllers\Fuel\PumpController;
use App\Http\Controllers\Fuel\TankController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesAnalysisController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sales
    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    Route::delete('sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

    // Fuel management
    Route::redirect('fuel', '/fuel/tanks');

    Route::get('fuel/types', [FuelTypeController::class, 'index'])->name('fuel.types.index');
    Route::post('fuel/types', [FuelTypeController::class, 'store'])->name('fuel.types.store');
    Route::put('fuel/types/{fuelType}', [FuelTypeController::class, 'update'])->name('fuel.types.update');
    Route::delete('fuel/types/{fuelType}', [FuelTypeController::class, 'destroy'])->name('fuel.types.destroy');

    Route::get('fuel/tanks', [TankController::class, 'index'])->name('fuel.tanks.index');
    Route::post('fuel/tanks', [TankController::class, 'store'])->name('fuel.tanks.store');
    Route::put('fuel/tanks/{tank}', [TankController::class, 'update'])->name('fuel.tanks.update');
    Route::delete('fuel/tanks/{tank}', [TankController::class, 'destroy'])->name('fuel.tanks.destroy');

    Route::get('fuel/pumps', [PumpController::class, 'index'])->name('fuel.pumps.index');
    Route::post('fuel/pumps', [PumpController::class, 'store'])->name('fuel.pumps.store');
    Route::put('fuel/pumps/{pump}', [PumpController::class, 'update'])->name('fuel.pumps.update');
    Route::delete('fuel/pumps/{pump}', [PumpController::class, 'destroy'])->name('fuel.pumps.destroy');

    Route::get('fuel/deliveries', [FuelDeliveryController::class, 'index'])->name('fuel.deliveries.index');
    Route::post('fuel/deliveries', [FuelDeliveryController::class, 'store'])->name('fuel.deliveries.store');
    Route::delete('fuel/deliveries/{delivery}', [FuelDeliveryController::class, 'destroy'])->name('fuel.deliveries.destroy');

    // Cash flow
    Route::get('cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');
    Route::post('cash-flow', [CashFlowController::class, 'store'])->name('cash-flow.store');
    Route::delete('cash-flow/{cashFlow}', [CashFlowController::class, 'destroy'])->name('cash-flow.destroy');

    // Sales analysis
    Route::get('analytics', [SalesAnalysisController::class, 'index'])->name('analytics.index');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});
