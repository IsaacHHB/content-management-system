<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingSeeder::class);
});

test('the signed preview route renders a draft page in the public layout', function () {
    $user = User::factory()->create();

    // A DRAFT (unpublished) page — the public catch-all would 404 on this.
    $page = Page::create([
        'title' => 'Secret Draft', 'slug' => 'secret-draft', 'blocks' => [],
        'status' => 'draft', 'created_by' => $user->id,
    ]);

    $url = URL::temporarySignedRoute('preview.pages', now()->addHour(), ['page' => $page->id]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('public/page')
            ->where('page.title', 'Secret Draft'));
});

test('the preview route rejects an unsigned request', function () {
    $user = User::factory()->create();
    $page = Page::create([
        'title' => 'Draft', 'slug' => 'draft', 'blocks' => [],
        'status' => 'draft', 'created_by' => $user->id,
    ]);

    $this->get("/preview/pages/{$page->id}")->assertForbidden();
});

test('the blocks endpoint saves a draft so preview reflects unsaved edits', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $page = Page::create([
        'title' => 'Draftable', 'slug' => 'draftable', 'blocks' => [],
        'status' => 'draft', 'created_by' => $user->id,
    ]);

    $this->actingAs($user)->patchJson(route('admin.pages.update-blocks', $page), [
        'blocks' => [[
            'id' => 'b1', 'type' => 'rich_text',
            'data' => ['content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello preview']]]]]],
        ]],
    ])->assertOk()->assertJson(['ok' => true]);

    expect($page->fresh()->blocks)->toHaveCount(1);
});

test('editing a page exposes a signed preview url', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $page = Page::create([
        'title' => 'Editable', 'slug' => 'editable', 'blocks' => [],
        'status' => 'draft', 'created_by' => $user->id,
    ]);

    $this->actingAs($user)->get(route('admin.pages.edit', $page))
        ->assertInertia(fn ($p) => $p
            ->where('previewUrl', fn ($url) => is_string($url) && str_contains($url, '/preview/pages/'.$page->id)));
});
