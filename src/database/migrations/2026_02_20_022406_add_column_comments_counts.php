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
        Schema::table('tips', function (Blueprint $table){
            $table->unsignedInteger('comment_count')->default(0)->comment('댓글 수 캐시(옵션');
            $table->index('comment_count', 'tips_comment_count_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropIndex('tips_comment_count_idx');
            $table->dropColumn('comment_count');
        });
    }
};
