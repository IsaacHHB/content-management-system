<?php

use App\Models\Category;
use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, SettingSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('programs support create update reorder and soft delete', function () {
    $this->actingAs($this->admin)->postJson(route('admin.programs.store'), [
        'title' => 'Fatherhood Program', 'excerpt' => 'Program description', 'blocks' => [],
        'status' => 'draft', 'sort_order' => 0,
    ])->assertRedirect();
    $first = Program::latest('id')->first();
    $this->postJson(route('admin.programs.store'), [
        'title' => 'Youth Program', 'excerpt' => 'Youth description', 'blocks' => [],
        'status' => 'published', 'sort_order' => 1,
    ])->assertRedirect();
    $second = Program::latest('id')->first();

    $this->putJson(route('admin.programs.update', $first->id), [
        'title' => 'Updated Fatherhood Program', 'slug' => $first->slug,
        'excerpt' => 'Updated description', 'blocks' => [], 'status' => 'draft',
    ])->assertRedirect();
    expect($first->fresh()->title)->toBe('Updated Fatherhood Program');

    $this->patchJson(route('admin.programs.reorder'), ['ids' => [$second->id, $first->id]])->assertRedirect();
    expect(Program::findOrFail($second->id)->sort_order)->toBe(0)
        ->and(Program::findOrFail($first->id)->sort_order)->toBe(1);

    $this->deleteJson(route('admin.programs.destroy', $first->id))->assertRedirect();
    expect(Program::withTrashed()->findOrFail($first->id)->trashed())->toBeTrue();
});

test('posts synchronize categories and sanitize their blocks', function () {
    $category = Category::create(['name' => 'Community']);
    $this->actingAs($this->admin)->postJson(route('admin.posts.store'), [
        'title' => 'Community News', 'excerpt' => 'News excerpt', 'status' => 'published',
        'category_ids' => [$category->id],
        'blocks' => [[
            'id' => 'copy', 'type' => 'rich_text',
            'data' => ['content' => ['type' => 'doc', 'content' => [['type' => 'text', 'text' => '<b>News</b>']]]],
        ]],
    ])->assertRedirect();

    $post = Post::latest('id')->first();
    expect($post->categories()->pluck('categories.id')->all())->toBe([$category->id])
        ->and($post->blocks[0]['data']['content']['content'][0]['text'])->toBe('News');
});

test('galleries synchronize ordered assets with required accessible alt text', function () {
    $asset = MediaAsset::create([
        'uuid' => (string) Str::uuid(), 'type' => 'image', 'original_name' => 'family.jpg',
        'created_by' => $this->admin->id, 'updated_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->postJson(route('admin.galleries.store'), [
        'title' => 'Community Gallery', 'description' => 'Photos', 'status' => 'published',
        'media_assets' => [['id' => $asset->id, 'alt_text' => 'Families at a community gathering', 'sort_order' => 0]],
    ])->assertRedirect();

    $gallery = Gallery::latest('id')->first();
    expect($gallery->mediaAssets()->firstOrFail()->pivot->alt_text)->toBe('Families at a community gathering')
        ->and($asset->isInUse())->toBeTrue();
});

test('team members support visibility controls and reordering', function () {
    $this->actingAs($this->admin)->postJson(route('admin.team.store'), [
        'name' => 'First Member', 'title' => 'Director', 'bio' => 'Biography',
        'email' => 'first@nativedadsnetwork.org', 'show_email' => false,
        'show_phone' => false, 'is_active' => true, 'sort_order' => 0,
    ])->assertRedirect();
    $first = TeamMember::latest('id')->first();
    $this->postJson(route('admin.team.store'), [
        'name' => 'Second Member', 'title' => 'Coordinator', 'bio' => 'Biography',
        'show_email' => false, 'show_phone' => false, 'is_active' => true, 'sort_order' => 1,
    ])->assertRedirect();
    $second = TeamMember::latest('id')->first();

    $this->patchJson(route('admin.team.reorder'), ['ids' => [$second->id, $first->id]])->assertRedirect();
    expect(TeamMember::findOrFail($second->id)->sort_order)->toBe(0);
});

test('menus accept one nested level with internal model references', function () {
    $page = Page::create(['title' => 'About', 'slug' => 'about', 'blocks' => [], 'status' => 'published', 'created_by' => $this->admin->id]);
    $menu = Menu::where('slot', 'header')->firstOrFail();

    $this->actingAs($this->admin)->putJson(route('admin.menus.update', $menu), [
        'name' => 'Main navigation',
        'items' => [[
            'label' => 'About', 'linkable_type' => Page::class, 'linkable_id' => $page->id,
            'opens_new_tab' => false,
            'children' => [[
                'label' => 'Contact', 'custom_url' => '/contact', 'opens_new_tab' => false,
            ]],
        ]],
    ])->assertRedirect();

    expect($menu->items()->count())->toBe(1)
        ->and($menu->items()->firstOrFail()->children()->count())->toBe(1);
});

test('editors cannot delete another authors draft', function () {
    $author = User::factory()->create();
    $otherEditor = User::factory()->create();
    $otherEditor->assignRole('editor');
    $page = Page::create(['title' => 'Author Draft', 'slug' => 'author-draft', 'blocks' => [], 'status' => 'draft', 'created_by' => $author->id]);

    $this->actingAs($otherEditor)->deleteJson(route('admin.pages.destroy', $page))->assertForbidden();
    expect($page->fresh())->not->toBeNull();
});
