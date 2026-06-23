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
        Schema::table('post_social_accounts', function (Blueprint $table) {

            $table->string('platform_post_id')
                ->nullable()
                ->after('social_account_id');

            $table->text('post_url')
                ->nullable()
                ->after('platform_post_id');

            $table->dropColumn([
                'instagram_post_id',
                'facebook_post_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_social_accounts', function (Blueprint $table) {

            $table->string('instagram_post_id')
                ->nullable();

            $table->string('facebook_post_id')
                ->nullable();

            $table->dropColumn([
                'platform_post_id',
                'post_url',
            ]);
        });
    }
};
