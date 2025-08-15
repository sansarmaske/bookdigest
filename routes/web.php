<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialiteController;

// Health check endpoints for monitoring
Route::get('/health', [HealthController::class, 'check'])->name('health.check');
Route::get('/health/liveness', [HealthController::class, 'liveness'])->name('health.liveness');
Route::get('/health/readiness', [HealthController::class, 'readiness'])->name('health.readiness');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('books.index');
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('books.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('books', BookController::class)->except(['show', 'edit', 'update']);

    // Book autocomplete endpoint
    Route::get('/api/books/autocomplete', [BookController::class, 'autocomplete'])->name('books.autocomplete');

    // Rate-limited quote generation - 10 requests per minute per user
    Route::middleware('throttle:quotes')->group(function () {
        Route::post('/books/{book}/quote', [BookController::class, 'generateQuote'])->name('books.generate-quote');
    });
});

// Social login routes
Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('social.callback');

require __DIR__.'/auth.php';
