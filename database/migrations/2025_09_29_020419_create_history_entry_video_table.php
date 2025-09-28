<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_entry_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_entry_id')->constrained()->onDelete('cascade')->comment('年表項目ID');
            $table->foreignId('video_id')->constrained()->onDelete('cascade')->comment('動画ID');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_entry_video');
    }
};
