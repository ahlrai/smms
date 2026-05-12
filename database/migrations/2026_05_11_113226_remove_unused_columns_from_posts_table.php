<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {

            if (Schema::hasColumn('posts', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('posts', 'platform_type')) {
                $table->dropColumn('platform_type');
            }

            if (Schema::hasColumn('posts', 'facebook_caption')) {
                $table->dropColumn('facebook_caption');
            }

            if (Schema::hasColumn('posts', 'instagram_caption')) {
                $table->dropColumn('instagram_caption');
            }

        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {

            $table->string('title')->nullable();

            $table->string('platform_type')->nullable();

            $table->text('facebook_caption')->nullable();

            $table->text('instagram_caption')->nullable();

        });
    }
};