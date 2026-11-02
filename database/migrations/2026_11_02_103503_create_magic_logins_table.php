<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Passwordless login links: hashed single-use tokens, email-scoped,
     * short TTL. Redeemed tokens reference the user that consumed them.
     */
    public function up(): void
    {
        Schema::create('magic_logins', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_logins');
    }
};
