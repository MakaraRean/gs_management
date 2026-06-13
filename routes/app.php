<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessUserController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Fuel\FuelDeliveryController;
use App\Http\Controllers\Fuel\FuelTypeController;
use App\Http\Controllers\Fuel\PumpController;
use App\Http\Controllers\Fuel\TankController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesAnalysisController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\TeamController;
use App\Http\Middleware\EnsureHasBusiness;
use App\Http\Middleware\EnsureHasStation;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding/business', [OnboardingController::class, 'storeBusiness'])->name('onboarding.business.store');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware(EnsureHasBusiness::class)->group(function () {
        // Team (members of the current business) + business settings
        Route::get('team', [TeamController::class, 'index'])->name('team.index');
        Route::post('team/members', [BusinessUserController::class, 'store'])->name('team.members.store');
        Route::delete('team/members/{member}', [BusinessUserController::class, 'destroy'])->name('team.members.destroy');
        Route::put('business', [BusinessController::class, 'update'])->name('business.update');

        // Stations
        Route::get('stations', [StationController::class, 'index'])->name('stations.index');
        Route::post('stations', [StationController::class, 'store'])->name('stations.store');
        Route::put('stations/{station}', [StationController::class, 'update'])->name('stations.update');
        Route::delete('stations/{station}', [StationController::class, 'destroy'])->name('stations.destroy');

        // Sales analysis
        Route::get('analytics', [SalesAnalysisController::class, 'index'])->name('analytics.index');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::middleware(EnsureHasStation::class)->group(function () {
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

            // Customers
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

            // Customer addresses
            Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store');
            Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update');
            Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');

            // Customer payments
            Route::post('customers/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('customers.payments.store');
            Route::delete('customers/{customer}/payments/{payment}', [CustomerPaymentController::class, 'destroy'])->name('customers.payments.destroy');
        });
    });
});
