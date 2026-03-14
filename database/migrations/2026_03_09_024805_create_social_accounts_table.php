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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['facebook', 'instagram']);
            $table->string('username');
            $table->string('account_id');                   // Page ID atau Instagram Business ID
            $table->text('access_token');                   // Disimpan terenkripsi
            $table->text('refresh_token')->nullable();      // Disimpan terenkripsi
            $table->timestamp('token_expired_at')->nullable();
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['platform', 'account_id']);     // 1 akun tidak bisa dobel
            $table->index('platform');
            $table->index('token_expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
