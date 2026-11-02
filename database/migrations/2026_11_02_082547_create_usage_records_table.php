<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 80);
            $table->bigInteger('quantity')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->unique(['organization_id', 'metric', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
