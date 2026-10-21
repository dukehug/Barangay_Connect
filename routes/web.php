<?php

use App\Http\Controllers\DocumentAttachmentController;
use App\Http\Controllers\DocumentPrintController;
use App\Http\Controllers\ResidentAuthController;
use App\Http\Controllers\ResidentDocumentController;
use App\Http\Controllers\ResidentPortalController;
use Illuminate\Support\Facades\Route;

Route::name('resident.')->group(function (): void {
    Route::view('/', 'resident.login')->name('login');
    Route::view('/register', 'resident.register')->name('register');
    Route::post('/login', [ResidentAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::post('/register', [ResidentAuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');

    Route::middleware('auth:resident')->group(function (): void {
        Route::post('/logout', [ResidentAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [ResidentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/announcements', [ResidentPortalController::class, 'announcements'])->name('announcements');
        Route::get('/announcements/{announcement}', [ResidentPortalController::class, 'announcement'])->name('announcement');
        Route::get('/documents', [ResidentPortalController::class, 'documents'])->name('documents');
        Route::post('/documents', [ResidentDocumentController::class, 'store'])->middleware('throttle:20,1')->name('documents.store');
        Route::get('/resident', fn () => redirect()->route('resident.dashboard'))->name('home');
        Route::get('/documents/create', [ResidentDocumentController::class, 'create'])->name('documents.create');
        Route::get('/documents/{document}', [ResidentDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/edit', [ResidentDocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{document}', [ResidentDocumentController::class, 'update'])->middleware('throttle:20,1')->name('documents.update');
        Route::post('/documents/{document}/respond', [ResidentDocumentController::class, 'respond'])->middleware('throttle:20,1')->name('documents.respond');
        Route::get('/document-attachments/{attachment}', [ResidentDocumentController::class, 'attachment'])->name('documents.attachment');
        Route::get('/complaints', [ResidentPortalController::class, 'complaints'])->name('complaints');
        Route::post('/complaints', [ResidentPortalController::class, 'storeComplaint'])->middleware('throttle:20,1')->name('complaints.store');
        Route::get('/complaints/{complaint}', [ResidentPortalController::class, 'showComplaint'])->whereNumber('complaint')->name('complaints.show');
        Route::get('/complaints/{complaint}/attachment', [ResidentPortalController::class, 'attachment'])->name('complaints.attachment');
        Route::get('/activity', [ResidentPortalController::class, 'activity'])->name('activity');
        Route::get('/profile', [ResidentPortalController::class, 'profile'])->name('profile');
        Route::patch('/profile', [ResidentPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/profile/photo', [ResidentPortalController::class, 'photo'])->name('profile.photo');
    });
});

Route::get('/admin/request-attachments/{attachment}', [ResidentDocumentController::class, 'staffAttachment'])->middleware('auth:employee')->name('admin.requests.attachment');

Route::get('/admin/document-requests/{document}/print', DocumentPrintController::class)->middleware('auth:employee')->name('admin.requests.print');

Route::get('/private-documents/preview', [DocumentAttachmentController::class, 'preview'])->middleware('signed')->name('documents.preview');
Route::delete('/document-attachments/{attachment}', [DocumentAttachmentController::class, 'destroy'])->middleware('auth:resident')->name('resident.documents.attachments.destroy');
Route::patch('/document-attachments/{attachment}/order', [DocumentAttachmentController::class, 'reorder'])->middleware('auth:resident')->name('resident.documents.attachments.order');
