<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('カテゴリID');
            $table->foreignId('space_id')->comment('所属スペースID')->constrained('spaces')->onDelete('cascade');
            $table->string('name')->comment('カテゴリ名');
            $table->integer('order_column')->default(0)->comment('並び順');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
