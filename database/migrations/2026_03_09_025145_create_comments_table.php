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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                  ->constrained('posts')
                  ->cascadeOnDelete();
            $table->foreignId('social_account_id')
                  ->constrained('social_accounts')
                  ->cascadeOnDelete();
            $table->string('platform_comment_id')->unique(); // ID komentar dari API
            $table->string('commenter_id')->nullable();      // User ID di platform
            $table->string('commenter_username');
            $table->string('commenter_avatar')->nullable();
            $table->enum('platform', ['facebook', 'instagram']);
            $table->text('content');
            $table->unsignedInteger('like_count')->default(0);
            $table->boolean('is_replied')->default(false);
            $table->boolean('is_hidden')->default(false);    // Untuk hide/unhide komentar
            $table->string('parent_comment_id')->nullable(); // Jika ini adalah reply dari komentar lain
            $table->timestamp('commented_at');               // Waktu komentar dari platform
            $table->timestamps();

            $table->index('post_id');
            $table->index('platform');
            $table->index('is_replied');
            $table->index('social_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
