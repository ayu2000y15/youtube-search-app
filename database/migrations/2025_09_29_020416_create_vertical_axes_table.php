<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vertical_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timeline_id')->constrained()->onDelete('cascade')->comment('年表ID');
            $table->string('label')->comment('縦軸のラベル');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vertical_axes');
    }
};
