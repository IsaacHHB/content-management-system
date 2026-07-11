<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            // Relative path (on the public disk) to the auto-generated social
            // share image composited from the member's photo, name, and title.
            $table->string('og_image_path')->nullable()->after('photo_media_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('og_image_path');
        });
    }
};
