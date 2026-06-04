<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    if (!Schema::hasTable('post_social_accounts')) {

        Schema::create('post_social_accounts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('post_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('social_account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }
}

    public function down(): void
    {
        Schema::dropIfExists('post_social_accounts');
    }
};