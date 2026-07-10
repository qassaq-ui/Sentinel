<?php

use App\Http\Controllers\InquiriesController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::post('locale', [LocalizationController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('inquiries', [InquiriesController::class, 'index'])->name('inquiries.index');
    Route::get('inquiries/create', [InquiriesController::class, 'create'])->name('inquiries.create');
    Route::get('users', [UsersController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');
    Route::post('users', [UsersController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');
    Route::patch('users/{user}', [UsersController::class, 'update'])
        ->middleware('permission:users.update')
        ->name('users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');
});

require __DIR__.'/settings.php';
