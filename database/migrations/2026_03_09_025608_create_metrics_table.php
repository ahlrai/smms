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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')
                  ->constrained('social_accounts')
                  ->cascadeOnDelete();
            $table->foreignId('post_id')
                  ->nullable()
                  ->constrained('posts')
                  ->nullOnDelete();          // Null = metrics level akun (bukan per post)
            $table->enum('platform', ['facebook', 'instagram']);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->date('recorded_date');                   // Tanggal data diambil
            $table->timestamps();

            $table->index(['social_account_id', 'recorded_date']);
            $table->index(['post_id', 'recorded_date']);
            $table->unique(['social_account_id', 'post_id', 'recorded_date']); // Hindari data dobel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
