<?php

use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\DictionariesController;
use App\Http\Controllers\InquiriesController;
use App\Http\Controllers\InquiryCommentsController;
use App\Http\Controllers\InquiryReportsController;
use App\Http\Controllers\InquiryResponseAttachmentsController;
use App\Http\Controllers\InquiryResponsesController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\PublicInquiryController;
use App\Http\Controllers\Settings\RolesPermissionsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicInquiryController::class, 'create'])->name('home');
Route::post('inquiries/status', [PublicInquiryController::class, 'status'])
    ->middleware('throttle:public-inquiry-status')
    ->name('public-inquiries.status');
Route::post('inquiries/status/response', [PublicInquiryController::class, 'response'])
    ->middleware('throttle:public-inquiry-status')
    ->name('public-inquiries.status.response');
Route::post('inquiries', [PublicInquiryController::class, 'store'])
    ->middleware('throttle:public-inquiries')
    ->name('public-inquiries.store');

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
    Route::patch('inquiries/{inquiry:number}/assignee', [InquiriesController::class, 'updateAssignee'])
        ->middleware('permission:inquiries.assign')
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
    Route::patch('inquiries/{inquiry:number}/response/draft', [InquiryResponsesController::class, 'draft'])
        ->middleware('permission:inquiries.respond')
        ->name('inquiries.response.draft');
    Route::post('inquiries/{inquiry:number}/response/generate', [InquiryResponsesController::class, 'generate'])
        ->middleware('permission:inquiries.respond')
        ->name('inquiries.response.generate');
    Route::post('inquiries/{inquiry:number}/response/transform', [InquiryResponsesController::class, 'transform'])
        ->middleware('permission:inquiries.respond')
        ->name('inquiries.response.transform');
    Route::post('inquiries/{inquiry:number}/response/submit', [InquiryResponsesController::class, 'submit'])
        ->middleware('permission:inquiries.respond')
        ->name('inquiries.response.submit');
    Route::patch('inquiries/{inquiry:number}/response/review', [InquiryResponsesController::class, 'review'])
        ->middleware('permission:inquiries.approve')
        ->name('inquiries.response.review');
    Route::post('inquiries/{inquiry:number}/response/send', [InquiryResponsesController::class, 'send'])
        ->middleware('permission:inquiries.send')
        ->name('inquiries.response.send');
    Route::post('inquiries/{inquiry:number}/comments', [InquiryCommentsController::class, 'store'])
        ->name('inquiries.comments.store');
    Route::delete('inquiries/{inquiry:number}/comments/{comment:uuid}', [InquiryCommentsController::class, 'destroy'])
        ->withoutScopedBindings()
        ->name('inquiries.comments.destroy');
    Route::get('inquiries/{inquiry:number}/comments/attachments/{attachment:uuid}', [InquiryCommentsController::class, 'download'])
        ->withoutScopedBindings()
        ->name('inquiries.comments.attachments.download');
    Route::get('inquiries/{inquiry:number}/response/attachments/{attachment:uuid}/download', [InquiryResponseAttachmentsController::class, 'download'])
        ->withoutScopedBindings()
        ->middleware('permission:inquiries.view')
        ->name('inquiries.response.attachments.download');
    Route::delete('inquiries/{inquiry:number}/response/attachments/{attachment:uuid}', [InquiryResponseAttachmentsController::class, 'destroy'])
        ->withoutScopedBindings()
        ->middleware('permission:inquiries.respond')
        ->name('inquiries.response.attachments.destroy');
    Route::post('inquiries/{inquiry:number}/report', [InquiryReportsController::class, 'store'])
        ->middleware('permission:inquiries.view')
        ->name('inquiries.report.store');
    Route::get('inquiries/{inquiry:number}/report', [InquiryReportsController::class, 'show'])
        ->middleware('permission:inquiries.view')
        ->name('inquiries.report.show');
    Route::get('inquiries/{inquiry:number}/report/{report}/download', [InquiryReportsController::class, 'download'])
        ->middleware('permission:inquiries.view')
        ->name('inquiries.report.download');
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
    Route::get('users/roles', [RolesPermissionsController::class, 'edit'])
        ->middleware('permission:roles.view')
        ->name('roles-permissions.index');
    Route::post('users/roles', [RolesPermissionsController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles-permissions.store');
    Route::patch('users/roles/{role}', [RolesPermissionsController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('roles-permissions.update');
    Route::delete('users/roles/{role}', [RolesPermissionsController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles-permissions.destroy');
    Route::patch('users/roles/{role}/permissions', [RolesPermissionsController::class, 'updatePermission'])
        ->middleware('permission:roles.permissions.update')
        ->name('roles-permissions.permissions.update');
});

require __DIR__.'/settings.php';
