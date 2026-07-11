<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('authorized editors can create signed preview links for drafts', function () {
    $this->seed(RolePermissionSeeder::class);
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $page = Page::create([
        'title' => 'Unpublished Preview', 'slug' => 'unpublished-preview',
        'blocks' => [], 'status' => 'draft', 'created_by' => $editor->id,
    ]);

    $url = $this->actingAs($editor)
        ->postJson(route('admin.preview-links.store', ['type' => 'pages', 'id' => $page->id]))
        ->assertOk()
        ->json('url');

    // The signed URL renders the draft in the real public layout.
    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('public/page')
            ->where('page.title', 'Unpublished Preview'));
});

test('unsigned preview requests are rejected', function () {
    $page = Page::create(['title' => 'Draft', 'slug' => 'draft', 'blocks' => [], 'status' => 'draft']);

    $this->getJson(route('preview.pages', $page))->assertForbidden();
});
