<?php

use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\GalleryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\ProgramController;
use App\Http\Controllers\Public\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
Route::get('programs/{slug}', [ProgramController::class, 'show'])->name('programs.show');

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
Route::get('events/{slug}', [EventController::class, 'show'])->name('events.show');

Route::get('news', [PostController::class, 'index'])->name('news.index');
Route::get('news/{slug}', [PostController::class, 'show'])->name('news.show');

Route::get('gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('gallery/{slug}', [GalleryController::class, 'show'])->name('gallery.show');

Route::get('about/team', [TeamController::class, 'index'])->name('team.index');
Route::get('about/team/{slug}', [TeamController::class, 'show'])->name('team.show');

Route::get('contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:3,10')->name('contact.store');

Route::redirect('dashboard', '/admin')->middleware(['auth', 'ndn.domain', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

// Hierarchical page catch-all — must stay last. Excludes reserved prefixes.
Route::get('/{slugPath}', [PageController::class, 'show'])
    ->where('slugPath', '^(?!admin|invite|login|register|forgot-password|reset-password|verify-email|two-factor|settings|storage|preview|build|api)[a-z0-9\-/]+$')
    ->name('pages.show');
