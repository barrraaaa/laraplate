<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coupons (PRD 5.5 P1): codes with discounts, eligibility and
     * redemption caps. Stripe linkage is optional — codes without a
     * stripe_coupon_id are validated locally and recorded locally.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('percent_off')->nullable();
            $table->unsignedInteger('amount_off')->nullable();
            $table->string('stripe_coupon_id')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['coupon_id', 'organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
