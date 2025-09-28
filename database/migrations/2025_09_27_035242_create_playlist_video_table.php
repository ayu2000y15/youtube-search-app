<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->comment('再生リストID')->constrained('playlists')->onDelete('cascade');
            $table->foreignId('video_id')->comment('動画ID')->constrained('videos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_video');
    }
};
