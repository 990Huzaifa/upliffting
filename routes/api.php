<?php

use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GeneralAnnouncementController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SurgeRateController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\VehicleTypeController;
use App\Http\Controllers\Admin\VehicleTypeRateController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Customer\PaymentMethodController as CustomerPaymentMethodController;
use App\Http\Controllers\Customer\RideController as CustomerRideController;
use App\Http\Controllers\Rider\RideController as RiderRideController;
use App\Http\Controllers\Rider\VehicleController as RiderVehicleController;
use App\Http\Controllers\Rider\VehicleInspectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\RiderController as AdminRiderController;
use App\Http\Controllers\Rider\AuthController as RiderAuth;
use App\Http\Controllers\Rider\ProfileController as RiderProfileController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\AuthController as CustomerAuth;

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



// for rider
Route::prefix('rider')->group(function () {
    Route::post('/signin', [RiderAuth::class, 'signin']);
    Route::post('/signup', [RiderAuth::class, 'signup']);
    Route::post('/resend-code', [RiderAuth::class, 'resendCode']);
    Route::post('/forgot-password', [RiderAuth::class, 'forgotPassword']);
    Route::post('/reset-password', [RiderAuth::class, 'resetPassword']);
    Route::put('/verify/{token}/{email}', [RiderAuth::class, 'verification']);
});

// for customer
Route::prefix('customer')->group(function () {
    Route::post('/signin', [CustomerAuth::class, 'signin']);
    Route::post('/signup', [CustomerAuth::class, 'signup']);
    Route::post('/resend-code', [CustomerAuth::class, 'resendCode']);
    Route::post('/forgot-password', [CustomerAuth::class, 'forgotPassword']);
    Route::post('/reset-password', [CustomerAuth::class, 'resetPassword']);
    Route::put('/verify/{token}/{email}', [CustomerAuth::class, 'verification']);
});

// for admin
Route::prefix('admin')->group(function () {
    Route::post('signin', [AdminAuth::class, 'signin'])->name('admin.signin');
});

Broadcast::routes(['middleware' => ['auth:api']]);

Route::middleware(['auth:sanctum'])->group(function () {

    // for admin
    Route::prefix('admin')->group(function () {

        Route::middleware('admin')->group(function () {

            Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

            // customer

            Route::apiResource('/customers', AdminCustomerController::class);
            Route::put('/customers/update-status/{id}', [AdminCustomerController::class, 'updateStatus'])->name('customers.updateStatus');


            // riders


            Route::prefix('/riders')
            ->controller(AdminRiderController::class)
            ->name('riders')
            ->group(function () {
                Route::get('/', 'index')->name('.index');
                Route::get('/{id}', 'show')->name('.show');
                Route::put('/approved/{id}/{status}',  'approvedStatus')->name('.approved');
                Route::post('/update-status',  'updateStatus')->name('.updateStatus');
                Route::post('/send-mail/{id}',  'sendMail')->name('.send-mail');    
            });

            //  vechicle
            Route::apiResource('vehicles',  AdminVehicleController::class)->only('index', 'show');
            Route::post('vehicles/approve-inspection/{id}', [AdminVehicleController::class, 'updateInspection'])->name('.update-inspection');

            
            Route::get('vehicles-inspection/{id}',  [AdminRiderController::class,'InspectionList'])->name('vehicles-inspection');

            Route::apiResource('/vehicle-type', VehicleTypeController::class)->only(['index', 'store', 'show', 'update']);

            Route::apiResource('/vehicle-type-rates', VehicleTypeRateController::class)->only(['index', 'store', 'show', 'update']);
            Route::get('list/vehicle-type',[VehicleTypeController::class,'list']);

            Route::get('list/vehicle-type-rates',[VehicleTypeRateController::class,'list1']);
            Route::post('vehicle-type-rates/{id}', [VehicleTypeRateController::class, 'update'])->name('.update');
            Route::apiResource('/vehicle-type-rates', VehicleTypeRateController::class)->only(['index', 'store', 'show']);

            // start from here csc
            Route::get('countries', [VehicleTypeRateController::class, 'country'])->name('countries');
            Route::get('states', [VehicleTypeRateController::class, 'state']);
            Route::get('states/{id}', [VehicleTypeRateController::class, 'statesByCountry']);
            Route::get('cities/{id}', [VehicleTypeRateController::class, 'cityByState']);
            // end here


            Route::apiResource('/contact', ContactController::class)->only(['index','show']);

            Route::apiResource('/surge-rates', SurgeRateController::class)->only(['index', 'store', 'show']);

            Route::apiResource('/general-announcement', GeneralAnnouncementController::class)->only(['index', 'store', 'show']);



            // settings

            Route::prefix('/settings')
            ->controller(SettingController::class)
            ->name('settings')
            ->group(function () {
                Route::get('/dataset', 'dataset')->name('.dataset');
                Route::post('/dataset-store', 'datasetStore')->name('.datasetStore');
            });
            
        });

        Route::get('/logout', [AdminAuth::class, 'logout']);
    });

    // for rider
    Route::prefix('rider')->group(function () {
        
        Route::controller(RiderAuth::class)->group(function () {
            
            
            Route::post('/setup', 'setup');
            Route::post('/change-password', 'changePassword');
            Route::get('/logout', 'logout');
            
            // verification apis
            Route::post('/profile-picture', 'profilePicture');
            Route::post('/driving-license', 'drivingLicense');
            Route::post('/vehicle-insurance', 'vehicleInsurance');
            Route::post('/registration-certificate', 'registrationCertificate');
            Route::post('/background-check', 'backgroundCheck');

            
        });

        // profile
        Route::controller(RiderProfileController::class)->group(function () {
            Route::get('/profile', 'profile');
            Route::post('/edit-profile', 'editProfile');
            Route::post('/add-card', 'addCard');
            Route::post('/add-bank', 'addBank');
            Route::post('/add-ss-number', 'addSSN');
            Route::put('/go-online', 'goOnline');
            Route::put('/is-pet', 'pet');
            Route::get('/activate-vehicle/{id}', 'activateVehicle');
            Route::get('/about', 'about');
            Route::get('/pnp', 'pnp');
            Route::get('/tnc', 'tnc');
            Route::post('/contact', 'contactStore');   

            Route::post('/update-lat-long', 'updateLatLong');
        });

        // vehicle apis
        Route::get('vehicle-type', [VehicleTypeRateController::class, 'list1']);
        Route::apiResource('/vehicle', RiderVehicleController::class)->only('index', 'store', 'update', 'destroy');
        Route::post('upload-vehicle-inspection', [VehicleInspectionController::class, 'storeOrUpdate']);


        // ride
        Route::post('accept-ride/{id}', [RiderRideController::class, 'acceptRide']);

        
        
    });
    
    
    // for customer
    Route::prefix('customer')->group(function () {


        Route::controller(CustomerAuth::class)->group(function () {
            Route::post('/edit-profile', 'editProfile');
            Route::post('/upload-profile-picture', 'uploadProfilePicture');
            Route::post('/update-lat-long', 'updateLatLong');
            Route::post('/change-password', 'changePassword');
            Route::get('/logout', 'logout');
            
        });



        Route::controller(CustomerProfileController::class)->group(function () {
            Route::get('/profile', 'profile');
            Route::get('/about', 'about');
            Route::get('/pnp', 'pnp');
            Route::get('/tnc', 'tnc');            
            Route::post('/contact', 'contactStore');            
        });


        Route::controller(CustomerPaymentMethodController::class)->group(function () {
            Route::get('/payment-methods', 'index');
            Route::post('/payment-methods', 'store');
            Route::get('/payment-methods/switch/{id}', 'switchAccount');
        });


        Route::post('calculate-fare', [CustomerRideController::class, 'calculateFare']);
        Route::post('request-ride', [CustomerRideController::class, 'store']);
        Route::post('cancel-ride/{id}', [CustomerRideController::class, 'cancelRide']);
        Route::get('vehicle-type', [VehicleTypeRateController::class, 'list2']);
        
    });
    

    
});
