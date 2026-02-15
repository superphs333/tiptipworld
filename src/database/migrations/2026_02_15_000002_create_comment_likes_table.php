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
        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // 같은 유저가 같은 댓글에 중복 좋아요를 못 누르게 제한
            $table->unique(['comment_id', 'user_id'], 'comment_likes_comment_user_unique');

            // 조회 최적화
            $table->index('comment_id', 'comment_likes_comment_id_index');
            $table->index('user_id', 'comment_likes_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_likes');
    }
};
