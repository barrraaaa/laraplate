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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_invoice_id')->unique();
            $table->string('number')->nullable();
            $table->integer('amount_due')->default(0);
            $table->integer('amount_paid')->default(0);
            $table->string('currency', 3)->default('usd');
            $table->string('status', 40)->default('open');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->string('pdf_url')->nullable();
            $table->string('hosted_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
