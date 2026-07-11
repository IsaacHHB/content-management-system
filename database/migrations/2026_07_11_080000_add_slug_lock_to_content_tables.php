<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A soft-deletable row keeps its slug in the unique index and would block a
     * new live row from reusing that slug forever. `slug_lock` is an
     * app-maintained discriminator (0 while live, the row id once trashed) that
     * is folded into each slug-unique index: live rows still collide with each
     * other (all 0), trashed rows never collide (distinct ids). Same portable
     * approach as the existing `parent_key` column — no engine-specific
     * generated columns.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedBigInteger('slug_lock')->default(0);
            $table->dropUnique(['parent_key', 'slug', 'locale']);
            $table->unique(['parent_key', 'slug', 'locale', 'slug_lock']);
        });

        foreach (['programs', 'events', 'posts', 'galleries', 'team_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('slug_lock')->default(0);
                $table->dropUnique(['slug']);
                $table->unique(['slug', 'slug_lock']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['parent_key', 'slug', 'locale', 'slug_lock']);
            $table->dropColumn('slug_lock');
            $table->unique(['parent_key', 'slug', 'locale']);
        });

        foreach (['programs', 'events', 'posts', 'galleries', 'team_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique(['slug', 'slug_lock']);
                $table->dropColumn('slug_lock');
                $table->unique(['slug']);
            });
        }
    }
};
