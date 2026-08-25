<?php

use App\Http\Controllers\JobApplicationController;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::bind('application', fn (string $value): JobApplication => request()->user()
        ->jobApplications()
        ->findOrFail($value));

    Route::resource('applications', JobApplicationController::class);
});

require __DIR__.'/settings.php';
