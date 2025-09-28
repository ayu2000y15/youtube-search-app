<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->onDelete('cascade')->comment('スペースID');
            $table->string('name')->comment('年表のタイトル');
            $table->text('description')->nullable()->comment('年表の説明');
            $table->string('horizontal_axis_label')->nullable()->comment('横軸のラベル');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timelines');
    }
};
