<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->onDelete('cascade')->comment('スペースID');
            $table->string('name')->comment('イベント名');
            $table->text('performers')->nullable()->comment('出演');
            $table->text('price_info')->nullable()->comment('料金');
            $table->text('description')->nullable()->comment('内容');
            $table->string('event_url', 2048)->nullable()->comment('イベントURL');
            $table->text('internal_memo')->nullable()->comment('内部用メモ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
