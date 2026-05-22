<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleInspectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin,fleet_manager'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/vehicles/import', [VehicleController::class, 'importForm'])->middleware('role:admin')->name('vehicles.import.form');
    Route::post('/vehicles/import', [VehicleController::class, 'import'])->middleware('role:admin')->name('vehicles.import');
    Route::get('/vehicles/scan/{token}', [VehicleController::class, 'scan'])->name('vehicles.scan');
    Route::get('/vehicles/{vehicle}/qr-label', [VehicleController::class, 'qrLabel'])->name('vehicles.qr-label');
    Route::get('/vehicles/{vehicle}/check-in', [VehicleInspectionController::class, 'checkInForm'])->name('vehicles.check-in.form');
    Route::post('/vehicles/{vehicle}/check-in', [VehicleInspectionController::class, 'checkIn'])->name('vehicles.check-in');
    Route::get('/vehicles/{vehicle}/manufacturer-checkout', [VehicleInspectionController::class, 'manufacturerCheckoutForm'])->name('vehicles.manufacturer-checkout.form');
    Route::post('/vehicles/{vehicle}/manufacturer-checkout', [VehicleInspectionController::class, 'manufacturerCheckout'])->name('vehicles.manufacturer-checkout');
    Route::resource('vehicles', VehicleController::class);

    Route::get('/vehicles/{vehicle}/loans/create', [LoanController::class, 'create'])->name('vehicles.loans.create');
    Route::post('/loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');
    Route::get('/loans/{loan}/return', [LoanController::class, 'returnForm'])->name('loans.return.form');
    Route::resource('loans', LoanController::class)->only(['index', 'create', 'store']);

    Route::resource('drivers', DriverController::class)->except(['show', 'destroy']);

    Route::get('/categories', [CategoryController::class, 'index'])->middleware('role:admin')->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin')->name('categories.store');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:admin')->name('users.update');
});
