<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usage alerts (PRD 5.6 P1): one alert per (metric, period) once the
     * threshold is crossed.
     */
    public function up(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->timestamp('alerted_at')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->dropColumn('alerted_at');
        });
    }
};
