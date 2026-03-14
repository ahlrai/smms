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
        Schema::create('user_social_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('social_account_id')
                  ->constrained('social_accounts')
                  ->cascadeOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_create_post')->default(true);
            $table->boolean('can_schedule_post')->default(true);
            $table->boolean('can_publish_post')->default(false);  // Hanya admin by default
            $table->boolean('can_reply_message')->default(true);
            $table->boolean('can_reply_comment')->default(true);
            $table->boolean('can_view_analytics')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'social_account_id']); // 1 record per user per akun
            $table->index('user_id');
            $table->index('social_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_permissions');
    }
};
