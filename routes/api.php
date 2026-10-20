<?php

use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('index');
Route::get('/get-lang', [LanguageController::class, 'getLang']);
Route::get('/get-basic', [HomeController::class, 'getBasic'])->name('getBasic');
Route::get('/get-categories', [HomeController::class, 'getCategories'])->name('getcategories');

Route::post('/save-fcm-token', [FcmTokenController::class, 'store']);
Route::get('/get-notifications', [FcmTokenController::class, 'getNotifications']);

//guest customer routes
Route::prefix('customer')->group(function () {
  Route::get('/signup', [UserController::class, 'signup'])->name('user.signup');
  Route::post('/signup/submit', [UserController::class, 'signupSubmit'])->name('user.signup_submit');

  Route::get('/login', [UserController::class, 'login'])->name('user.login');
  Route::post('/login/submit', [UserController::class, 'loginSubmit'])->name('user.login_submit');

  Route::post('/forget-password', [UserController::class, 'forgetPassword'])->name('user.forget_password');
  Route::post('/reset-password', [UserController::class, 'resetPasswordSubmit'])->name('user.reset_password');
});

//authenticated customer routes
Route::prefix('/customer')->middleware('auth.sanctum')->group(function () {
  Route::get('/dashboard', [UserController::class, 'redirectToDashboard'])->name('user.dashboard');
  Route::get('/wishlist', [UserController::class, 'wishlist'])->name('user.wishlist');

  Route::get('/edit-profile', [UserController::class, 'editProfile'])->name('user.edit_profile');
  Route::post('/update-profile', [UserController::class, 'updateProfile'])->name('user.update_profile');
  Route::get('/change-password', [UserController::class, 'changePassword'])->name('user.change_password');
  Route::post('/update-password', [UserController::class, 'updatePassword'])->name('user.update_password');

  Route::get('addto/wishlist/{id}', [UserController::class, 'add_to_wishlist'])->name('user.wishlist')->middleware('auth.sanctum');
  Route::get('remove/wishlist/{id}', [UserController::class, 'remove_wishlist'])->name('remove.wishlist')->middleware('auth.sanctum');

  Route::prefix('support-ticket')->group(function () {
    Route::get('/', [SupportTicketController::class, 'index'])->name('api.user.support_ticket');
    Route::get('/create', [SupportTicketController::class, 'create'])->name('user.support_ticket.create');
    Route::post('store', [SupportTicketController::class, 'store'])->name('user.support_ticket.store');
    Route::get('message/{id}',  [SupportTicketController::class, 'message'])->name('user.support_ticket.message');
    Route::post('reply/{id}',  [SupportTicketController::class, 'reply'])->name('user.support_ticket.reply');
  });

  Route::post('/logout', [UserController::class, 'logoutSubmit'])->name('user.logout');
});


//cars routes
Route::prefix('cars')->group(function () {
  Route::get('/', [CarController::class, 'index'])->name('frontend.cars');

  Route::get('/search', [CarController::class, 'searchCar'])->name('frontend.cars.search');

  Route::get('/details/{id}', [CarController::class, 'details'])->name('frontend.cars.details');
  Route::post('/send-inquiry-message', [CarController::class, 'inQuiryMessage'])->name('frontend.cars.inQuiryMessage');


  Route::get('addto/wishlist/{id}', [UserController::class, 'add_to_wishlist'])->name('user.wishlist')->middleware('auth:sanctum');
  Route::get('remove/wishlist/{id}', [UserController::class, 'remove_wishlist'])->name('remove.wishlist')->middleware('auth:sanctum');
});

//vendors management routes
Route::get('/vendors', [VendorController::class, 'index'])->name('frontend.vfendors');
//vendor details page route
Route::get('vendor/{username}', [VendorController::class, 'details'])->name('frontend.vendor.details');
Route::post('/vendor/contact', [VendorController::class, 'contact'])->name('frontend.vendor.contact');
