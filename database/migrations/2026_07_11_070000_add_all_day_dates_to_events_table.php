<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('ends_at');
            $table->date('end_date')->nullable()->after('start_date');
            $table->dateTime('starts_at')->nullable()->change();
            $table->index(['all_day', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['all_day', 'start_date']);
            $table->dropColumn(['start_date', 'end_date']);
            $table->dateTime('starts_at')->nullable(false)->change();
        });
    }
};
