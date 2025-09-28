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
        Schema::table('event_schedules', function (Blueprint $table) {
            // 既存の scheduled_at カラムを削除
            $table->dropColumn('scheduled_at');

            // 新しいカラムを追加
            $table->date('performance_date')->after('session_name')->comment('公演日');
            $table->time('doors_open_time')->nullable()->after('performance_date')->comment('開場時間');
            $table->time('start_time')->nullable()->after('doors_open_time')->comment('開演時間');
            $table->time('end_time')->nullable()->after('start_time')->comment('終演時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 元に戻す処理
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->after('session_name');

            $table->dropColumn([
                'performance_date',
                'doors_open_time',
                'start_time',
                'end_time'
            ]);
        });
    }
};
