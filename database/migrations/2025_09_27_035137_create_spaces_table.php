<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id()->comment('スペースID');
            $table->foreignId('user_id')->comment('所有者のユーザーID')->constrained('users')->onDelete('cascade');
            $table->string('name')->comment('スペース名');
            $table->string('slug')->unique()->comment('URL用の識別子');
            $table->tinyInteger('visibility')->default(0)->comment('公開範囲 (0:自分のみ, 1:限定公開, 2:全体公開)');
            $table->string('invite_token')->nullable()->unique()->comment('招待用トークン');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
