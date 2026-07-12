<?php

use App\Models\MediaAsset;
use App\Models\Menu;
use App\Models\Page;
use App\Models\User;
use App\Services\BlockRenderer;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('an admin cannot promote a user to super administrator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();
    $target->assignRole('editor');

    $this->actingAs($admin)->putJson(route('admin.users.update', $target), [
        'role' => 'super_admin',
    ])->assertUnprocessable();

    expect($target->refresh()->hasRole('super_admin'))->toBeFalse()
        ->and($target->hasRole('editor'))->toBeTrue();
});

test('an admin cannot self-escalate to super administrator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->putJson(route('admin.users.update', $admin), [
        'role' => 'super_admin',
    ])->assertUnprocessable();

    expect($admin->refresh()->hasRole('super_admin'))->toBeFalse();
});

test('a super administrator can still grant the super administrator role', function () {
    $superAdmin = User::factory()->withTwoFactor()->create();
    $superAdmin->assignRole('super_admin');

    $target = User::factory()->create();
    $target->assignRole('admin');

    $this->actingAs($superAdmin)->putJson(route('admin.users.update', $target), [
        'role' => 'super_admin',
    ])->assertRedirect();

    expect($target->refresh()->hasRole('super_admin'))->toBeTrue();
});

test('a super administrator with an unconfirmed two factor secret is still forced to configure it', function () {
    $superAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code'])),
        'two_factor_confirmed_at' => null,
    ]);
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)->get(route('admin.dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('nested card and accordion content is sanitized server side', function () {
    $blocks = app(BlockRenderer::class)->sanitize([
        ['id' => 'cards-1', 'type' => 'cards', 'data' => [
            'columns' => 3,
            'cards' => [
                [
                    'title' => '<script>alert(1)</script>Card',
                    'media_asset_id' => '5',
                    'link' => ['label' => 'Go', 'url' => '//evil.example/x'],
                    'content' => ['type' => 'doc', 'content' => [
                        ['type' => 'text', 'text' => '<img src=x onerror=alert(1)>Body'],
                    ]],
                ],
            ],
        ]],
        ['id' => 'faq-1', 'type' => 'accordion', 'data' => [
            'items' => [
                ['heading' => '<b>Q</b>', 'content' => ['type' => 'doc', 'content' => [
                    ['type' => 'text', 'text' => '<img src=x onerror=alert(1)>A'],
                ]]],
            ],
        ]],
    ]);

    $card = $blocks[0]['data']['cards'][0];
    expect($card['title'])->toBe('alert(1)Card')
        ->and($card['media_asset_id'])->toBe(5)
        ->and($card['content']['content'][0]['text'])->toBe('Body')
        ->and($blocks[1]['data']['items'][0]['heading'])->toBe('Q')
        ->and($blocks[1]['data']['items'][0]['content']['content'][0]['text'])->toBe('A');
});

test('protocol-relative and backslash urls are rejected as unsafe links', function () {
    $blocks = app(BlockRenderer::class)->sanitize([
        ['id' => 'cta-1', 'type' => 'cta_banner', 'data' => [
            'heading' => 'Hi',
            'button' => ['label' => 'Go', 'url' => '//evil.example/x'],
        ]],
    ]);

    expect($blocks[0]['data']['button']['url'])->toBe('');
});

test('media referenced by a scalar setting value cannot be deleted', function () {
    $this->seed(SettingSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $asset = MediaAsset::create([
        'uuid' => (string) Str::uuid(), 'type' => 'image', 'original_name' => 'logo.png',
        'created_by' => $admin->id, 'updated_by' => $admin->id,
    ]);

    // Store the media id as a bare scalar (the format the audit found unprotected).
    $this->actingAs($admin)->putJson(route('admin.settings.update'), [
        'settings' => ['logo' => $asset->id],
    ])->assertRedirect();

    expect($asset->isInUse())->toBeTrue();

    $this->actingAs($admin)->deleteJson(route('admin.media.destroy', $asset))
        ->assertUnprocessable();

    expect(MediaAsset::find($asset->id))->not->toBeNull();
});

test('deactivated accounts are rejected at login regardless of authentication path', function () {
    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole('editor');

    event(new Login('web', $user, false));
})->throws(AuthenticationException::class);

test('deactivated sessions are terminated on every web route including fortify endpoints', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->getJson(route('passkey.registration-options'))
        ->assertUnauthorized();

    $this->assertGuest();
});

test('web responses include nonce based browser security headers', function () {
    $response = $this->get('/')->assertOk();
    $policy = (string) $response->headers->get('Content-Security-Policy');

    expect($policy)->toContain("default-src 'self'")
        ->and($policy)->toContain("object-src 'none'")
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Referrer-Policy'))->toBe('no-referrer');

    preg_match("/'nonce-([^']+)'/", $policy, $matches);
    expect($matches[1] ?? null)->not->toBeNull()
        ->and($response->getContent())->toContain('nonce="'.($matches[1] ?? '').'"');
});

test('menu custom urls reject script and protocol relative targets', function (string $url) {
    $this->seed(SettingSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $menu = Menu::where('slot', 'header')->firstOrFail();

    $this->actingAs($admin)->putJson(route('admin.menus.update', $menu), [
        'name' => 'Header',
        'items' => [[
            'label' => 'Unsafe',
            'custom_url' => $url,
            'linkable_type' => null,
            'linkable_id' => null,
            'opens_new_tab' => false,
            'children' => [],
        ]],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.0.custom_url');

    expect($menu->items()->count())->toBe(0);
})->with(['javascript:alert(1)', '//evil.example/path', '/\\evil.example/path']);

test('the sitemap exposes published content and excludes drafts', function () {
    Page::create(['title' => 'Public', 'slug' => 'public-page', 'blocks' => [], 'status' => 'published', 'published_at' => now()]);
    Page::create(['title' => 'Draft', 'slug' => 'draft-page', 'blocks' => [], 'status' => 'draft']);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(url('/public-page'), false)
        ->assertDontSee(url('/draft-page'), false);
});

test('profile email addresses are normalized before uniqueness checks and storage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => '  UPDATED@NATIVEDADSNETWORK.ORG ',
    ])->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email)->toBe('updated@nativedadsnetwork.org');
});

test('missing media references return validation errors instead of database errors', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)->postJson(route('admin.pages.store'), [
        'parent_id' => null,
        'title' => 'Invalid media page',
        'slug' => 'invalid-media-page',
        'blocks' => [[
            'id' => 'image-1',
            'type' => 'image',
            'data' => ['media_asset_id' => 999999, 'alt' => 'Missing'],
        ]],
        'status' => 'draft',
        'locale' => 'en',
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks');

    expect(Page::where('slug', 'invalid-media-page')->exists())->toBeFalse();
});
