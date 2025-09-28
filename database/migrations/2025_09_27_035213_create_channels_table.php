<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id()->comment('チャンネルID');
            $table->foreignId('space_id')->comment('所属スペースID')->constrained('spaces')->onDelete('cascade');
            $table->string('youtube_channel_id')->comment('YouTubeチャンネルID');
            $table->string('name')->comment('チャンネル名');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
