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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_event_id')->unique();
            $table->string('event_type');
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('usd');
            $table->string('status', 40)->default('succeeded');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
