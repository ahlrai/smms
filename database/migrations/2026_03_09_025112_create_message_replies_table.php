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
        Schema::create('message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                  ->constrained('messages')
                  ->cascadeOnDelete();
            $table->text('reply');
            $table->foreignId('replied_by')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('platform_reply_id')->nullable(); // ID balasan di platform (setelah terkirim)
            $table->boolean('is_sent')->default(false);      // Apakah sudah terkirim ke platform
            $table->timestamp('sent_at')->nullable();        // Waktu terkirim ke platform
            $table->timestamps();

            $table->index('message_id');
            $table->index('replied_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_replies');
    }
};
