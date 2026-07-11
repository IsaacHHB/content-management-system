<?php

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\Program;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('published scope excludes drafts and future scheduled content', function () {
    $user = User::factory()->create();
    Program::create(['title' => 'Live', 'excerpt' => 'Live', 'blocks' => [], 'status' => PublishStatus::Published, 'published_at' => now()->subMinute(), 'created_by' => $user->id]);
    Program::create(['title' => 'Future', 'excerpt' => 'Future', 'blocks' => [], 'status' => PublishStatus::Published, 'published_at' => now()->addDay(), 'created_by' => $user->id]);
    Program::create(['title' => 'Draft', 'excerpt' => 'Draft', 'blocks' => [], 'status' => PublishStatus::Draft, 'created_by' => $user->id]);

    expect(Program::published()->pluck('title')->all())->toBe(['Live']);
});

test('page slugs are unique among root siblings and may repeat under different parents', function () {
    $user = User::factory()->create();
    $first = Page::create(['title' => 'First', 'slug' => 'about', 'blocks' => [], 'status' => 'draft', 'created_by' => $user->id]);
    $parent = Page::create(['title' => 'Parent', 'slug' => 'parent', 'blocks' => [], 'status' => 'draft', 'created_by' => $user->id]);
    $child = Page::create(['parent_id' => $parent->id, 'title' => 'Child', 'slug' => 'about', 'blocks' => [], 'status' => 'draft', 'created_by' => $user->id]);

    expect($first->path)->toBe('/about')->and($child->path)->toBe('/parent/about');
    expect(fn () => Page::create(['title' => 'Duplicate', 'slug' => 'about', 'blocks' => [], 'status' => 'draft']))->toThrow(QueryException::class);
});

test('renaming a page creates redirects for it and its descendants', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $page = Page::create(['title' => 'About', 'slug' => 'about', 'blocks' => [], 'status' => 'draft', 'created_by' => $editor->id, 'updated_by' => $editor->id]);
    Page::create(['parent_id' => $page->id, 'title' => 'History', 'slug' => 'history', 'blocks' => [], 'status' => 'draft', 'created_by' => $editor->id, 'updated_by' => $editor->id]);

    $this->actingAs($editor)->putJson(route('admin.pages.update', $page), [
        'parent_id' => null, 'title' => 'Who We Are', 'slug' => 'who-we-are', 'blocks' => [],
        'status' => 'draft', 'published_at' => null, 'locale' => 'en',
    ])->assertOk();

    expect(Redirect::where('from_path', '/about')->value('to_path'))->toBe('/who-we-are')
        ->and(Redirect::where('from_path', '/about/history')->value('to_path'))->toBe('/who-we-are/history');
});
