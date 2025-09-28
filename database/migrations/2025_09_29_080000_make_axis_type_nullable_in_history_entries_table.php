<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // カラム修正には doctrine/dbal が必要な場合があります：composer require doctrine/dbal
        Schema::table('history_entries', function (Blueprint $table) {
            $table->string('axis_type')->nullable()->comment('軸の型 (date or custom)')->change();
        });
    }

    public function down(): void
    {
        Schema::table('history_entries', function (Blueprint $table) {
            $table->string('axis_type')->comment('軸の型 (date or custom)')->change();
        });
    }
};
