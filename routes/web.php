<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClassificationRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketMessageController;
use App\Http\Controllers\UserTicketController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/bitrix/install', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('bitrix.install');

Route::match(['get', 'post'], '/bitrix/handler', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('bitrix.handler');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:user')->prefix('tickets')->name('user.tickets.')->group(function () {
        Route::get('/new', [UserTicketController::class, 'create'])->name('create');
        Route::post('/', [UserTicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [UserTicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/messages', [TicketMessageController::class, 'userIndex'])->name('messages.index');
        Route::post('/{ticket}/messages', [TicketMessageController::class, 'userStore'])->name('messages.store');
    });

    Route::middleware('role:admin')->prefix('admin/tickets')->name('admin.tickets.')->group(function () {
        Route::get('/', [AdminTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [AdminTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [AdminTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/complete', [AdminTicketController::class, 'complete'])->name('complete');
        Route::delete('/{ticket}', [AdminTicketController::class, 'destroy'])->name('destroy');
        Route::get('/{ticket}/messages', [TicketMessageController::class, 'adminIndex'])->name('messages.index');
        Route::post('/{ticket}/messages', [TicketMessageController::class, 'adminStore'])->name('messages.store');
    });

    Route::middleware('role:admin')->prefix('admin/classification-rules')->name('admin.classification-rules.')->group(function () {
        Route::get('/', [ClassificationRuleController::class, 'index'])->name('index');
        Route::get('/create', [ClassificationRuleController::class, 'create'])->name('create');
        Route::post('/', [ClassificationRuleController::class, 'store'])->name('store');
        Route::get('/{classificationRule}/edit', [ClassificationRuleController::class, 'edit'])->name('edit');
        Route::put('/{classificationRule}', [ClassificationRuleController::class, 'update'])->name('update');
    });

    Route::middleware('role:admin')->prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('role:support')->prefix('support/tickets')->name('support.tickets.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [SupportTicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [SupportTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/complete', [SupportTicketController::class, 'complete'])->name('complete');
        Route::get('/{ticket}/messages', [TicketMessageController::class, 'supportIndex'])->name('messages.index');
        Route::post('/{ticket}/messages', [TicketMessageController::class, 'supportStore'])->name('messages.store');
    });
});
