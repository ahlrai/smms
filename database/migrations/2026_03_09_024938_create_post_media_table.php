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
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                  ->constrained('posts')
                  ->cascadeOnDelete();
            $table->string('file_path');                    // Path file di storage
            $table->string('file_name')->nullable();        // Nama asli file
            $table->enum('media_type', ['image', 'video']);
            $table->unsignedBigInteger('file_size')->nullable(); // Ukuran file dalam bytes
            $table->string('mime_type')->nullable();        // image/jpeg, video/mp4, dst
            $table->string('platform_media_id')->nullable(); // ID media di platform (setelah upload)
            $table->unsignedTinyInteger('sort_order')->default(0); // Urutan carousel
            $table->timestamps();

            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
