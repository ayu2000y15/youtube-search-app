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
        Schema::table('videos', function (Blueprint $table) {
            $table->bigInteger('view_count')->nullable()->after('published_at');
            $table->integer('like_count')->nullable()->after('view_count');
            $table->integer('comment_count')->nullable()->after('like_count');
            $table->text('description')->nullable()->after('comment_count');
            $table->json('tags')->nullable()->after('description');
            $table->string('category_id')->nullable()->after('tags');
            $table->string('language')->nullable()->after('category_id');
            $table->timestamp('statistics_updated_at')->nullable()->after('language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'view_count',
                'like_count',
                'comment_count',
                'description',
                'tags',
                'category_id',
                'language',
                'statistics_updated_at'
            ]);
        });
    }
};
