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
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            // 팔로우를 건 유저
            $table->foreignId('follower_user_id')->constrained('users')->cascadeOnDelete();
            // 팔로우를 받는 유저
            $table->foreignId('followed_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // 중복 팔로우 방지
            $table->unique(['follower_user_id','followed_user_id'],'user_follows_follower_followed_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
