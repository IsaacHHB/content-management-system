<?php

use App\Models\Event;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Services\SiteChrome;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('the last active super administrator cannot be deactivated with a non-boolean falsy value', function () {
    $super = User::factory()->withTwoFactor()->create(['is_active' => true]);
    $super->assignRole('super_admin');

    // "0" is what a form-encoded request sends; a strict `=== false` check misses it.
    $this->actingAs($super)->putJson(route('admin.users.update', $super), [
        'is_active' => '0',
    ])->assertStatus(422);

    expect($super->refresh()->is_active)->toBeTrue();
});

test('a super administrator can still be deactivated while another remains', function () {
    $super = User::factory()->withTwoFactor()->create(['is_active' => true]);
    $super->assignRole('super_admin');
    $other = User::factory()->withTwoFactor()->create(['is_active' => true]);
    $other->assignRole('super_admin');

    $this->actingAs($super)->putJson(route('admin.users.update', $other), [
        'is_active' => '0',
    ])->assertRedirect();

    expect($other->refresh()->is_active)->toBeFalse();
});

test('the content security policy lets the browser apply inline style attributes', function () {
    $csp = $this->get(route('login'))->headers->get('Content-Security-Policy');

    // A nonce in style-src makes browsers ignore 'unsafe-inline' there, so SSR-emitted
    // style="..." attributes (the admin sidebar's --sidebar-width) need style-src-attr.
    expect($csp)->toContain("style-src-attr 'unsafe-inline'");
});

test('the content security policy allows the video embed host the block renderer uses', function () {
    $csp = $this->get(route('login'))->headers->get('Content-Security-Policy');
    $frameSrc = collect(explode(';', (string) $csp))
        ->first(fn (string $directive) => str_contains($directive, 'frame-src'));

    expect($frameSrc)->toContain('https://www.youtube-nocookie.com')
        ->and($frameSrc)->toContain('https://player.vimeo.com');
});

test('robots.txt advertises the sitemap with an absolute url', function () {
    $body = $this->get('/robots.txt')->assertOk()->getContent();

    expect($body)->toContain('Sitemap: '.route('sitemap'))
        ->and(route('sitemap'))->toStartWith('http');
});

test('an all-day event happening today is upcoming and is not also listed as past', function () {
    $author = User::factory()->create();
    $author->assignRole('admin');

    $event = Event::create([
        'title' => 'Today All Day', 'slug' => 'today-all-day', 'description' => [],
        'status' => 'published', 'published_at' => now()->subDay(),
        'all_day' => true, 'start_date' => today(), 'end_date' => today(),
        'timezone' => 'America/Los_Angeles', 'is_virtual' => false,
        'created_by' => $author->id, 'updated_by' => $author->id,
    ]);

    $upcoming = Event::published()->upcoming()->pluck('id');
    $past = Event::published()->past()->pluck('id');

    expect($upcoming)->toContain($event->id)
        ->and($past)->not->toContain($event->id);
});

test('renaming a page busts the cached public menu that links to it', function () {
    $page = Page::create([
        'title' => 'About', 'slug' => 'about', 'path' => '/about', 'locale' => 'en',
        'blocks' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $menu = Menu::create(['name' => 'Header', 'slot' => 'header']);
    MenuItem::create([
        'menu_id' => $menu->id, 'label' => 'About', 'linkable_type' => Page::class,
        'linkable_id' => $page->id, 'opens_new_tab' => false, 'sort_order' => 0,
    ]);

    Cache::forget('public_menus');
    app(SiteChrome::class)->menus();
    expect(Cache::has('public_menus'))->toBeTrue();

    $page->update(['slug' => 'about-us', 'path' => '/about-us']);

    // The cache holds *resolved* URLs, so a re-slug must invalidate it or the nav
    // keeps linking at the dead /about path forever.
    expect(Cache::has('public_menus'))->toBeFalse();

    $menus = app(SiteChrome::class)->menus();
    expect($menus['header'][0]['url'])->toBe('/about-us');
});
