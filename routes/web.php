<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GeneralAnnouncementController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\SurgeRateController;
use App\Http\Controllers\Admin\VehicleTypeRateController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Rider\ProfileController as RiderProfileController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return "Hello World!";
});
Route::get('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return 'Optimization cache cleared!';
});

Route::prefix('rider')->controller(RiderProfileController::class)->group(function () {
    Route::get('/stripe/onboarding/refresh/{id}', 'refreshOnboardingLink');
    Route::get('/stripe/onboarding/success/{id}', 'successOnboardingLink');
});


