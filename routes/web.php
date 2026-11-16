<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Task 9: profile routes here (auth only, no permission middleware)

    Route::middleware('permission:users.view')->resource('admin/users', UserController::class)->except(['show'])->names('admin.users');
    Route::middleware('permission:users.activate')->group(function () {
        Route::post('admin/users/{user}/activate', [UserController::class, 'activate'])->name('admin.users.activate');
        Route::post('admin/users/{user}/reject', [UserController::class, 'reject'])->name('admin.users.reject');
        Route::post('admin/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('admin.users.deactivate');
    });
    Route::middleware('permission:roles.view')->resource('admin/roles', RoleController::class)->except(['show'])->names('admin.roles');
    Route::middleware('permission:permissions.view')->get('/admin/permissions', [PermissionController::class, 'index'])->name('admin.permissions.index');

    // Profile (auth only, no permission middleware)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
