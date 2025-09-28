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
        Schema::create('ticket_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade')->comment('イベントID');
            $table->string('sale_method_name')->comment('販売手法の名称');
            $table->dateTime('app_starts_at')->nullable()->comment('申込受付期間 (開始)');
            $table->dateTime('app_ends_at')->nullable()->comment('申込受付期間 (終了)');
            $table->dateTime('results_at')->nullable()->comment('抽選結果発表日時');
            $table->dateTime('payment_starts_at')->nullable()->comment('支払手続期間 (開始)');
            $table->dateTime('payment_ends_at')->nullable()->comment('支払手続期間 (終了)');
            $table->text('notes')->nullable()->comment('注意事項');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sales');
    }
};
