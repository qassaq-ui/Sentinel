<?php

use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\InquirySettingsController;
use App\Http\Controllers\Settings\LocalizationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('settings', [GeneralSettingsController::class, 'edit'])
        ->middleware('permission:settings.access')
        ->name('settings.index');
    Route::post('settings/localization', [LocalizationController::class, 'store'])
        ->middleware('permission:settings.access')
        ->name('settings.localization.store');
    Route::patch('settings/localization/{locale}', [LocalizationController::class, 'update'])
        ->middleware('permission:settings.access')
        ->name('settings.localization.update');
    Route::patch('settings/inquiries', [InquirySettingsController::class, 'update'])
        ->middleware('permission:settings.access')
        ->name('settings.inquiries.update');
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
