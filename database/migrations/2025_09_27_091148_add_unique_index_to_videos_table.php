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
        Schema::table('videos', function (Blueprint $table) {
            // space_idとyoutube_video_idの複合ユニークインデックスを追加
            $table->unique(['space_id', 'youtube_video_id'], 'videos_space_youtube_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // ユニークインデックスを削除
            $table->dropUnique('videos_space_youtube_unique');
        });
    }
};
