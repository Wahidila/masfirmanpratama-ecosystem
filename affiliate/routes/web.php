<?php

use App\Http\Controllers\Admin\AdminAffiliatorController;
use App\Http\Controllers\Admin\AdminCommissionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminMaterialController;
use App\Http\Controllers\Admin\AdminWithdrawalController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\Webhooks\StoreWebhookController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureAffiliatorIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/ref/{code}', [ReferralController::class, 'track'])->name('referral.track');

/*
|--------------------------------------------------------------------------
| Guest Routes (unauthenticated only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest:affiliator')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:affiliator')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Email verification
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Pending approval page
    Route::get('/pending-approval', function () {
        return view('auth.pending-approval');
    })->name('pending-approval');

    // Active affiliator routes (verified + active)
    Route::middleware(['verified', EnsureAffiliatorIsActive::class])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Referral links
        Route::resource('referrals', ReferralController::class)->except(['show']);
        Route::post('/referrals/{referral}/toggle', [ReferralController::class, 'toggle'])->name('referrals.toggle');

        // Commissions
        Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');

        // Withdrawals
        Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawals.create');
        Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');

        // Materials
        Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
        Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');

        // Events & Leaderboard
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::post('/events/{event}/join', [EventController::class, 'join'])->name('events.join');
        Route::get('/leaderboard', [EventController::class, 'leaderboard'])->name('leaderboard');

        // Rewards
        Route::get('/rewards', [EventController::class, 'rewards'])->name('rewards.index');
        Route::post('/rewards/{reward}/claim', [EventController::class, 'claimReward'])->name('rewards.claim');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/bank', [ProfileController::class, 'updateBank'])->name('profile.bank');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    // Admin auth (guest)
    Route::middleware('guest:affiliator')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('admin.login.submit');
    });

    // Admin authenticated
    Route::middleware(AdminAuthenticate::class)->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Affiliators
        Route::get('/affiliators', [AdminAffiliatorController::class, 'index'])->name('admin.affiliators.index');
        Route::get('/affiliators/{affiliator}', [AdminAffiliatorController::class, 'show'])->name('admin.affiliators.show');
        Route::post('/affiliators/{affiliator}/approve', [AdminAffiliatorController::class, 'approve'])->name('admin.affiliators.approve');
        Route::post('/affiliators/{affiliator}/suspend', [AdminAffiliatorController::class, 'suspend'])->name('admin.affiliators.suspend');
        Route::post('/affiliators/{affiliator}/reactivate', [AdminAffiliatorController::class, 'reactivate'])->name('admin.affiliators.reactivate');
        Route::delete('/affiliators/{affiliator}', [AdminAffiliatorController::class, 'destroy'])->name('admin.affiliators.destroy');

        // Commissions
        Route::get('/commissions', [AdminCommissionController::class, 'index'])->name('admin.commissions.index');
        Route::get('/commissions/settings', [AdminCommissionController::class, 'settings'])->name('admin.commissions.settings');
        Route::put('/commissions/settings', [AdminCommissionController::class, 'updateSettings'])->name('admin.commissions.settings.update');

        // Withdrawals
        Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::post('/withdrawals/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');

        // Materials
        Route::get('/materials', [AdminMaterialController::class, 'index'])->name('admin.materials.index');
        Route::get('/materials/create', [AdminMaterialController::class, 'create'])->name('admin.materials.create');
        Route::post('/materials', [AdminMaterialController::class, 'store'])->name('admin.materials.store');
        Route::delete('/materials/{material}', [AdminMaterialController::class, 'destroy'])->name('admin.materials.destroy');
        Route::post('/materials/{material}/toggle', [AdminMaterialController::class, 'toggle'])->name('admin.materials.toggle');

        // Events
        Route::get('/events', [AdminEventController::class, 'index'])->name('admin.events.index');
        Route::get('/events/create', [AdminEventController::class, 'create'])->name('admin.events.create');
        Route::post('/events', [AdminEventController::class, 'store'])->name('admin.events.store');
        Route::get('/events/{event}/edit', [AdminEventController::class, 'edit'])->name('admin.events.edit');
        Route::put('/events/{event}', [AdminEventController::class, 'update'])->name('admin.events.update');
        Route::delete('/events/{event}', [AdminEventController::class, 'destroy'])->name('admin.events.destroy');
        Route::post('/events/{event}/activate', [AdminEventController::class, 'activate'])->name('admin.events.activate');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (no auth, CSRF excluded via bootstrap/app.php)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/store', [StoreWebhookController::class, 'handle'])->name('webhooks.store');
