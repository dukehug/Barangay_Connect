<?php

use App\Http\Controllers\ResidentAuthController;
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
        Route::post('/documents', [ResidentPortalController::class, 'storeDocument'])->middleware('throttle:20,1')->name('documents.store');
        Route::get('/complaints', [ResidentPortalController::class, 'complaints'])->name('complaints');
        Route::post('/complaints', [ResidentPortalController::class, 'storeComplaint'])->middleware('throttle:20,1')->name('complaints.store');
        Route::get('/complaints/{complaint}/attachment', [ResidentPortalController::class, 'attachment'])->name('complaints.attachment');
        Route::get('/activity', [ResidentPortalController::class, 'activity'])->name('activity');
        Route::get('/profile', [ResidentPortalController::class, 'profile'])->name('profile');
        Route::patch('/profile', [ResidentPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/profile/photo', [ResidentPortalController::class, 'photo'])->name('profile.photo');
    });
});
