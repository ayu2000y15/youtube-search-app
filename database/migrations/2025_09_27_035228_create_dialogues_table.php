<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialogues', function (Blueprint $table) {
            $table->id()->comment('文字起こしID');
            $table->foreignId('video_id')->comment('所属動画ID')->constrained('videos')->onDelete('cascade');
            $table->integer('timestamp')->comment('タイムスタンプ(秒)');
            $table->string('speaker')->nullable()->comment('発言者');
            $table->text('dialogue')->comment('文字起こし');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialogues');
    }
};
