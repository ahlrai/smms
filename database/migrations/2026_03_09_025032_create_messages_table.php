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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')
                  ->constrained('social_accounts')
                  ->cascadeOnDelete();
            $table->string('platform_message_id')->unique(); // ID pesan dari API (untuk de-duplikasi saat sync)
            $table->string('sender_id')->nullable();         // User ID pengirim di platform
            $table->string('sender_username');               // Username / nama pengirim
            $table->string('sender_avatar')->nullable();     // URL avatar pengirim
            $table->enum('platform', ['facebook', 'instagram']);
            $table->text('message');
            $table->enum('status', ['new', 'follow-up', 'resolved'])->default('new');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at');                    // Waktu pesan dikirim (dari platform)
            $table->timestamps();

            $table->index('status');
            $table->index('platform');
            $table->index('is_read');
            $table->index('sent_at');
            $table->index('social_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
