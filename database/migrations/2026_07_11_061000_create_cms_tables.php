<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('pending_email')->nullable()->unique();
            $table->string('role', 50)->default('editor');
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 50);
            $table->string('original_name');
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->string('credit')->nullable();
            $table->json('focal_point')->nullable();
            $table->string('status', 30)->default('ready');
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            // Kept explicitly (rather than as a vendor-specific generated column)
            // so root-page uniqueness behaves identically in SQLite and MySQL.
            $table->unsignedBigInteger('parent_key')->default(0);
            $table->string('title');
            $table->string('slug');
            $table->json('blocks')->default(new Expression("('[]')"));
            $this->publishingColumns($table);
            $this->seoColumns($table);
            $table->foreignId('og_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('locale', 8)->default('en');
            $table->boolean('is_locked')->default(false);
            $table->integer('sort_order')->default(0);
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['parent_key', 'slug', 'locale']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->json('blocks')->default(new Expression("('[]')"));
            $this->publishingColumns($table);
            $this->seoColumns($table);
            $table->foreignId('og_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('external_url')->nullable();
            $table->integer('sort_order')->default(0);
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('description')->default(new Expression("('[]')"));
            $this->publishingColumns($table);
            $this->seoColumns($table);
            $table->foreignId('og_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('timezone', 64)->default('America/Los_Angeles');
            $table->string('location_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('zip', 20)->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->string('virtual_url')->nullable();
            $table->string('registration_url')->nullable();
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'starts_at']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->json('blocks')->default(new Expression("('[]')"));
            $this->publishingColumns($table);
            $this->seoColumns($table);
            $table->foreignId('og_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $this->publishingColumns($table);
            $table->integer('sort_order')->default(0);
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gallery_media_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['gallery_id', 'media_asset_id']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('bio');
            $table->string('email')->nullable();
            $table->boolean('show_email')->default(false);
            $table->string('phone', 50)->nullable();
            $table->boolean('show_phone')->default(false);
            $table->foreignId('photo_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $this->auditColumns($table);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slot', 30)->unique();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label');
            $table->nullableMorphs('linkable');
            $table->string('custom_url')->nullable();
            $table->boolean('opens_new_tab')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group', 30)->index();
            $table->timestamps();
        });

        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('ip_hash', 64)->nullable()->index();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('media_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->morphs('referencer');
            $table->string('block_id')->nullable();
            $table->string('field');
            $table->timestamps();
            $table->index(['media_asset_id', 'referencer_type', 'referencer_id'], 'media_reference_usage');
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('media_references');
        Schema::dropIfExists('contact_submissions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('gallery_media_asset');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('events');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('invites');
    }

    private function publishingColumns(Blueprint $table): void
    {
        $table->string('status', 20)->default('draft');
        $table->timestamp('published_at')->nullable();
    }

    private function seoColumns(Blueprint $table): void
    {
        $table->string('seo_title')->nullable();
        $table->string('seo_description')->nullable();
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    }
};
