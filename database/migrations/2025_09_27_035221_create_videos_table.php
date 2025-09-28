<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id()->comment('動画ID');
            $table->foreignId('space_id')->comment('所属スペースID')->constrained('spaces')->onDelete('cascade');
            $table->foreignId('channel_id')->comment('所属チャンネルID')->constrained('channels')->onDelete('cascade');
            $table->string('youtube_video_id')->comment('YouTube動画ID');
            $table->string('title')->comment('動画タイトル');
            $table->string('thumbnail_url')->comment('サムネイルURL');
            $table->dateTime('published_at')->comment('YouTubeでの公開日時');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
