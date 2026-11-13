<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Task 9: profile routes here (auth only, no permission middleware)

    Route::middleware('permission:users.view')->resource('admin/users', UserController::class)->except(['show'])->names('admin.users');
    Route::middleware('permission:roles.view')->resource('admin/roles', RoleController::class)->except(['show'])->names('admin.roles');
    Route::middleware('permission:permissions.view')->get('/admin/permissions', [PermissionController::class, 'index'])->name('admin.permissions.index');

    // Task 9 replaces this placeholder with the real ProfileController.
    Route::view('/profile', 'admin.profile')->name('profile');
});
