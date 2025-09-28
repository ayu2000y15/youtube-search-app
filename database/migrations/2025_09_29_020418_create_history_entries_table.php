<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertical_axis_id')->constrained()->onDelete('cascade')->comment('縦軸ID');
            $table->foreignId('history_category_id')->constrained()->onDelete('cascade')->comment('カテゴリID');
            $table->string('axis_type')->comment('軸の型 (date or custom)');
            $table->dateTime('axis_date')->nullable()->comment('軸の値（リアル日付用）');
            $table->string('axis_custom_value')->nullable()->comment('軸の値（カスタム用）');
            $table->integer('display_order')->default(0)->comment('同じ座標での並び順');
            $table->string('character_name')->nullable()->comment('対象キャラクター名');
            $table->string('title')->comment('タイトル');
            $table->text('content')->nullable()->comment('内容');
            $table->json('related_urls')->nullable()->comment('関連URL');
            $table->text('memo')->nullable()->comment('メモ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_entries');
    }
};
