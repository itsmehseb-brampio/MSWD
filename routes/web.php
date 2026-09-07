<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminAccountsController;
use App\Http\Controllers\BarangayManagementController;
use App\Http\Controllers\MunicipalContactsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DisasterController;
use App\Http\Controllers\HazardMapController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\ReliefController;
use App\Http\Controllers\BarangayDashboardController;
use App\Http\Controllers\BarangayOverviewController;
use App\Http\Controllers\BarangayInfoController;
use App\Http\Controllers\BarangayHazardMapController;
use App\Http\Controllers\BarangayDisasterController;
use App\Http\Controllers\BarangayMessagesController;
use App\Http\Controllers\BarangayAnnouncementsController;
use App\Http\Controllers\BarangayReliefController;
use App\Http\Controllers\Api\HazardMapApiController;
use App\Http\Controllers\Api\AdminAnnouncementApiController;
use App\Http\Controllers\Api\AdminMessageApiController;
use App\Http\Controllers\Api\AdminReliefApiController;
use App\Http\Controllers\Api\AdminReliefDistributionApiController;
use App\Http\Controllers\Api\BarangayAnnouncementApiController;
use App\Http\Controllers\Api\BarangayMessageApiController;
use App\Http\Controllers\Api\BarangayReliefApiController;
use App\Http\Controllers\Api\BarangayReliefDistributionApiController;
use Illuminate\Support\Facades\Route;

// Single unified login page for MSWD (admin) and Barangay accounts
Route::get('/', [LoginController::class, 'show'])->name('landing');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// ============== MSWD / ADMIN ROUTES ==============
Route::prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));

    // Guest (unified login page)
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn () => redirect()->route('login'))->name('admin.login');
        Route::post('/login', fn () => redirect()->route('login'))->name('admin.login.post');
    });

    // Authenticated admin
    Route::middleware('auth:admin')->group(function () {
        Route::get('/logout', [LoginController::class, 'logout'])->name('admin.logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/admins', [AdminAccountsController::class, 'index'])->name('admin.admins');
        Route::post('/admins', [AdminAccountsController::class, 'store'])->name('admin.admins.store');
        Route::post('/admins/update', [AdminAccountsController::class, 'update'])->name('admin.admins.update');
        Route::post('/admins/delete', [AdminAccountsController::class, 'destroy'])->name('admin.admins.delete');

        Route::get('/barangays', [BarangayManagementController::class, 'index'])->name('admin.barangay');
        Route::post('/barangays', [BarangayManagementController::class, 'store'])->name('admin.barangay.store');

        Route::get('/municipal-contacts', [MunicipalContactsController::class, 'index'])->name('admin.municipal');
        Route::post('/municipal-contacts', [MunicipalContactsController::class, 'update'])->name('admin.municipal.update');

        Route::get('/contact', [ContactController::class, 'index'])->name('admin.contact');

        Route::get('/disaster/format', [DisasterController::class, 'format'])->name('admin.disaster.format');
        Route::post('/disaster/format', [DisasterController::class, 'formatStore'])->name('admin.disaster.format.store');
        Route::get('/disaster/pending', [DisasterController::class, 'pending'])->name('admin.disaster.pending');
        Route::get('/disaster/approved', [DisasterController::class, 'approved'])->name('admin.disaster.approved');
        Route::get('/disaster/declined', [DisasterController::class, 'declined'])->name('admin.disaster.declined');
        Route::get('/disaster/reedit', [DisasterController::class, 'reedit'])->name('admin.disaster.reedit');
        Route::get('/disaster/history', [DisasterController::class, 'history'])->name('admin.disaster.history');
        Route::post('/disaster/review', [DisasterController::class, 'review'])->name('admin.disaster.review');

        Route::get('/hazard-map', [HazardMapController::class, 'index'])->name('admin.hazard_map');
        Route::get('/messages', [MessagesController::class, 'index'])->name('admin.messages');
        Route::get('/announcements', [AnnouncementsController::class, 'index'])->name('admin.announcements');
        Route::get('/relief', [ReliefController::class, 'index'])->name('admin.relief');
        Route::get('/relief/distributions', [ReliefController::class, 'distributions'])->name('admin.relief.distributions');
    });

    // Admin JSON APIs
    Route::prefix('api')->middleware('auth:admin')->group(function () {
        Route::post('/announcements/{action}', [AdminAnnouncementApiController::class, 'handle']);
        Route::post('/messages/{action}', [AdminMessageApiController::class, 'handle']);
        Route::post('/relief/{action}', [AdminReliefApiController::class, 'handle']);
        Route::post('/relief-distribution/{action}', [AdminReliefDistributionApiController::class, 'handle']);
    });
});

// ============== BARANGAY ROUTES ==============
Route::prefix('barangay')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));

    // Guest (unified login page)
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn () => redirect()->route('login'))->name('barangay.login');
        Route::post('/login', fn () => redirect()->route('login'))->name('barangay.login.post');
    });

    // Authenticated barangay
    Route::middleware('auth:barangay')->group(function () {
        Route::get('/logout', [LoginController::class, 'logout'])->name('barangay.logout');
        Route::get('/dashboard', [BarangayDashboardController::class, 'index'])->name('barangay.dashboard');
        Route::get('/overview', [BarangayOverviewController::class, 'index'])->name('barangay.overview');
        Route::get('/info', [BarangayInfoController::class, 'index'])->name('barangay.info');
        Route::post('/info', [BarangayInfoController::class, 'update'])->name('barangay.info.update');
        Route::get('/hazard-map', [BarangayHazardMapController::class, 'edit'])->name('barangay.hazard_map');
        Route::post('/hazard-map', [BarangayHazardMapController::class, 'update'])->name('barangay.hazard_map.update');
        Route::get('/hazard-map/view', [BarangayHazardMapController::class, 'view'])->name('barangay.hazard_map_view');

        Route::get('/disaster/apply', [BarangayDisasterController::class, 'apply'])->name('barangay.disaster.apply');
        Route::post('/disaster/apply', [BarangayDisasterController::class, 'store'])->name('barangay.disaster.store');
        Route::get('/disaster/approved', [BarangayDisasterController::class, 'approved'])->name('barangay.disaster.approved');
        Route::get('/disaster/pending', [BarangayDisasterController::class, 'pending'])->name('barangay.disaster.pending');
        Route::get('/disaster/declined', [BarangayDisasterController::class, 'declined'])->name('barangay.disaster.declined');
        Route::get('/disaster/history', [BarangayDisasterController::class, 'history'])->name('barangay.disaster.history');
        Route::get('/disaster/edit/{report}', [BarangayDisasterController::class, 'edit'])->name('barangay.disaster.edit');
        Route::post('/disaster/update/{report}', [BarangayDisasterController::class, 'update'])->name('barangay.disaster.update');
        Route::get('/disaster/reedit', [BarangayDisasterController::class, 'reedit'])->name('barangay.disaster.reedit');

        Route::get('/messages', [BarangayMessagesController::class, 'index'])->name('barangay.messages');
        Route::get('/announcements', [BarangayAnnouncementsController::class, 'index'])->name('barangay.announcements');
        Route::get('/relief', [BarangayReliefController::class, 'index'])->name('barangay.relief');
    });

    // Barangay JSON APIs
    Route::prefix('api')->middleware('auth:barangay')->group(function () {
        Route::post('/hazard-map/{action}', [HazardMapApiController::class, 'handle']);
        Route::post('/announcements/{action}', [BarangayAnnouncementApiController::class, 'handle']);
        Route::post('/messages/{action}', [BarangayMessageApiController::class, 'handle']);
        Route::post('/relief/{action}', [BarangayReliefApiController::class, 'handle']);
        Route::post('/relief-distribution/{action}', [BarangayReliefDistributionApiController::class, 'handle']);
    });
});