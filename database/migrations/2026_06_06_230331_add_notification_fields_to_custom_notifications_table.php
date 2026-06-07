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
    Schema::table('custom_notifications', function ($table) {

        $table->string('platform')->nullable();

        $table->string('post_title')->nullable();

        $table->string('status')->nullable();

        $table->string('post_url')->nullable();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_notifications', function (Blueprint $table) {
            //
        });
    }
};
