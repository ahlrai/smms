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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')
                  ->constrained('social_accounts')
                  ->cascadeOnDelete();
            $table->enum('platform', ['facebook', 'instagram']);
            $table->text('caption');
            $table->enum('status', ['draft', 'scheduled', 'published', 'failed'])
                  ->default('draft');
            $table->string('platform_post_id')->nullable();  // ID post di FB/IG setelah publish
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('fail_reason')->nullable();
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at');
            $table->index('platform');
            $table->index(['status', 'scheduled_at']); // Composite untuk query publish terjadwal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
