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
        Schema::create('tip_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tip_id')->constrained('tips')->cascadeOnDelete(); 
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

        // 같은 유저가 같은 팁에 중복 좋아요 방지
            $table->unique(['tip_id', 'user_id'],'tip_likes_tip_user_unique');

            // 조회 최적화(조회 패턴별로 명시)
            $table->index('tip_id', 'tip_likes_tip_id_index');
            $table->index('user_id', 'tip_likes_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tip_likes');
    }
};
