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

            if (! Schema::hasColumn('post_social_accounts', 'platform_post_id')) {
                $table->string('platform_post_id')
                    ->nullable()
                    ->after('social_account_id');
            }

            if (! Schema::hasColumn('post_social_accounts', 'post_url')) {
                $table->text('post_url')
                    ->nullable()
                    ->after('platform_post_id');
            }

            if (Schema::hasColumn('post_social_accounts', 'instagram_post_id')) {
                $table->dropColumn('instagram_post_id');
            }

            if (Schema::hasColumn('post_social_accounts', 'facebook_post_id')) {
                $table->dropColumn('facebook_post_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_social_accounts', function (Blueprint $table) {

            if (! Schema::hasColumn('post_social_accounts', 'instagram_post_id')) {
                $table->string('instagram_post_id')
                    ->nullable();
            }

            if (! Schema::hasColumn('post_social_accounts', 'facebook_post_id')) {
                $table->string('facebook_post_id')
                    ->nullable();
            }

            if (Schema::hasColumn('post_social_accounts', 'platform_post_id')) {
                $table->dropColumn('platform_post_id');
            }

            if (Schema::hasColumn('post_social_accounts', 'post_url')) {
                $table->dropColumn('post_url');
            }
        });
    }
};