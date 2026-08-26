<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\MoveJobApplicationController;
use App\Http\Controllers\PipelineController;
use App\Models\Contact;
use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('pipeline', PipelineController::class)->name('pipeline.index');

    Route::bind('application', fn (string $value): JobApplication => request()->user()
        ->jobApplications()
        ->findOrFail($value));
    Route::bind('contact', fn (string $value): Contact => request()->user()
        ->contacts()
        ->findOrFail($value));
    Route::bind('interview', fn (string $value): Interview => Interview::query()
        ->whereIn('job_application_id', request()->user()->jobApplications()->select('id'))
        ->findOrFail($value));

    Route::resource('applications', JobApplicationController::class);
    Route::resource('contacts', ContactController::class);
    Route::resource('interviews', InterviewController::class);
    Route::patch('pipeline/applications/{application}', MoveJobApplicationController::class)
        ->name('pipeline.move');
});

require __DIR__.'/settings.php';
