<?php

use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\DictionariesController;
use App\Http\Controllers\InquiriesController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::post('locale', [LocalizationController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::post('ai-assistant/chat', [AIAssistantController::class, 'chat'])
        ->name('ai-assistant.chat');
    Route::get('dictionaries', [DictionariesController::class, 'index'])
        ->middleware('permission:dictionaries.view')
        ->name('dictionaries.index');
    Route::post('dictionaries/inquiry-categories', [DictionariesController::class, 'store'])
        ->middleware('permission:dictionaries.create')
        ->name('dictionaries.inquiry-categories.store');
    Route::patch('dictionaries/inquiry-categories/{category}', [DictionariesController::class, 'update'])
        ->middleware('permission:dictionaries.update')
        ->name('dictionaries.inquiry-categories.update');
    Route::delete('dictionaries/inquiry-categories/{category}', [DictionariesController::class, 'destroy'])
        ->middleware('permission:dictionaries.delete')
        ->name('dictionaries.inquiry-categories.destroy');
    Route::patch('dictionaries/inquiry-outcomes/{outcome}', [DictionariesController::class, 'updateOutcome'])
        ->middleware('permission:dictionaries.update')
        ->name('dictionaries.inquiry-outcomes.update');
    Route::get('inquiries', [InquiriesController::class, 'index'])
        ->middleware('permission:inquiries.view')
        ->name('inquiries.index');
    Route::get('inquiries/create', [InquiriesController::class, 'create'])
        ->middleware('permission:inquiries.create')
        ->name('inquiries.create');
    Route::patch('inquiries/{inquiry:number}/assignee', [InquiriesController::class, 'updateAssignee'])
        ->middleware('permission:inquiries.update')
        ->name('inquiries.assignee.update');
    Route::patch('inquiries/{inquiry:number}/category', [InquiriesController::class, 'updateCategory'])
        ->middleware('permission:inquiries.update')
        ->name('inquiries.category.update');
    Route::get('inquiries/{inquiry:number}', [InquiriesController::class, 'show'])
        ->middleware('permission:inquiries.view')
        ->name('inquiries.show');
    Route::post('inquiries/{inquiry:number}/translate', [InquiriesController::class, 'translate'])
        ->middleware('permission:inquiries.view')
        ->name('inquiries.translate');
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
