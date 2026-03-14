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
        Schema::create('comment_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')
                  ->constrained('comments')
                  ->cascadeOnDelete();
            $table->text('reply');
            $table->foreignId('replied_by')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('platform_reply_id')->nullable(); // ID reply di platform setelah terkirim
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('comment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_replies');
    }
};
