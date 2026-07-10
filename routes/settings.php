<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RolesPermissionsController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::inertia('settings', 'settings/Index')->name('settings.index');
    Route::get('settings/roles-permissions', [RolesPermissionsController::class, 'edit'])
        ->middleware('permission:roles.view')
        ->name('roles-permissions.index');
    Route::post('settings/roles-permissions', [RolesPermissionsController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles-permissions.store');
    Route::patch('settings/roles-permissions/{role}', [RolesPermissionsController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('roles-permissions.update');
    Route::delete('settings/roles-permissions/{role}', [RolesPermissionsController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles-permissions.destroy');
    Route::patch('settings/roles-permissions/{role}/permissions', [RolesPermissionsController::class, 'updatePermission'])
        ->middleware('permission:roles.permissions.update')
        ->name('roles-permissions.permissions.update');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

});
