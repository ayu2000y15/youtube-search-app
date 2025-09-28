<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id()->comment('再生リストID');
            $table->foreignId('space_id')->comment('所属スペースID')->constrained('spaces')->onDelete('cascade');
            $table->string('youtube_playlist_id')->comment('YouTube再生リストID');
            $table->string('title')->comment('再生リストのタイトル');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
