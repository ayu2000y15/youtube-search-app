<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // published_atカラムの後にvideo_typeカラムを追加
            $table->string('video_type')->nullable()->after('published_at')->comment('動画種別 (video, shortなど)');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('video_type');
        });
    }
};
