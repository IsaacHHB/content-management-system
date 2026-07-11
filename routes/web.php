<?php

use App\Http\Controllers\Public\ContactController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:3,10')->name('contact.store');

Route::redirect('dashboard', '/admin')->middleware(['auth', 'ndn.domain', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
