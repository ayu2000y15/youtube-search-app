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
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('event_category_id')->nullable()->after('space_id')
                ->constrained('event_categories')->onDelete('restrict')
                ->comment('イベントカテゴリID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // 外部キー制約を先に削除してから、カラムを削除する
            $table->dropForeign(['event_category_id']);
            $table->dropColumn('event_category_id');
        });
    }
};
