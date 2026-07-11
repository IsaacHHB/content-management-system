<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactSubmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\InviteController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PreviewLinkController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AcceptInviteController;
use App\Http\Controllers\Public\PreviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ndn.domain', 'verified', 'superadmin.2fa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::patch('programs/reorder', [ProgramController::class, 'reorder'])->name('programs.reorder');
        Route::patch('galleries/reorder', [GalleryController::class, 'reorder'])->name('galleries.reorder');
        Route::patch('team/reorder', [TeamMemberController::class, 'reorder'])->name('team.reorder');
        Route::post('preview-links/{type}/{id}', PreviewLinkController::class)->name('preview-links.store');
        Route::post('pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore');
        Route::delete('pages/{page}/force', [PageController::class, 'forceDelete'])->name('pages.force-delete');
        Route::post('programs/{program}/restore', [ProgramController::class, 'restore'])->name('programs.restore');
        Route::delete('programs/{program}/force', [ProgramController::class, 'forceDelete'])->name('programs.force-delete');
        Route::post('events/{event}/restore', [EventController::class, 'restore'])->name('events.restore');
        Route::delete('events/{event}/force', [EventController::class, 'forceDelete'])->name('events.force-delete');
        Route::post('posts/{post}/restore', [PostController::class, 'restore'])->name('posts.restore');
        Route::delete('posts/{post}/force', [PostController::class, 'forceDelete'])->name('posts.force-delete');
        Route::post('galleries/{gallery}/restore', [GalleryController::class, 'restore'])->name('galleries.restore');
        Route::delete('galleries/{gallery}/force', [GalleryController::class, 'forceDelete'])->name('galleries.force-delete');
        Route::post('team/{teamMember}/restore', [TeamMemberController::class, 'restore'])->name('team.restore');
        Route::delete('team/{teamMember}/force', [TeamMemberController::class, 'forceDelete'])->name('team.force-delete');
        Route::resource('pages', PageController::class)->except('show');
        Route::resource('programs', ProgramController::class)->except('show');
        Route::resource('events', EventController::class)->except('show');
        Route::resource('posts', PostController::class)->except('show');
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::resource('galleries', GalleryController::class)->except('show');
        Route::resource('team', TeamMemberController::class)->except('show')->parameters(['team' => 'teamMember']);
        Route::resource('media', MediaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('menus', MenuController::class)->only(['index', 'update']);
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::resource('contacts', ContactSubmissionController::class)->only(['index', 'update', 'destroy']);
        Route::get('activity', ActivityController::class)->name('activity.index');

        Route::middleware('role:super_admin|admin')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'update']);
            Route::resource('invites', InviteController::class)->only(['index', 'store', 'destroy']);
            Route::post('invites/{invite}/resend', [InviteController::class, 'resend'])->name('invites.resend');
        });
    });

Route::middleware('guest')->group(function () {
    Route::get('invite/{token}', [AcceptInviteController::class, 'show'])
        ->middleware('signed')->name('invite.show');
    Route::post('invite/{token}', [AcceptInviteController::class, 'store'])
        ->middleware(['signed', 'throttle:5,1'])->name('invite.accept');
});

Route::middleware(['signed', 'throttle:30,1'])->prefix('preview')->name('preview.')->group(function () {
    Route::get('pages/{page}', [PreviewController::class, 'page'])->name('pages');
    Route::get('programs/{program}', [PreviewController::class, 'program'])->name('programs');
    Route::get('events/{event}', [PreviewController::class, 'event'])->name('events');
    Route::get('posts/{post}', [PreviewController::class, 'post'])->name('posts');
});
