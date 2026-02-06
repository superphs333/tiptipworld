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
        Schema::create('tips', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');

            $table->unsignedBigInteger('user_id')->nullable()->comment('작성자');
            $table->unsignedBigInteger('category_id')->nullable()->comment('카테고리');
            $table->string('title', 120)->comment('제목');
            $table->longText('content')->comment('본문(에디터/마크다운/HTML 가능)');
            $table->string('excerpt', 255)->nullable()->comment('요약(리스트용)');

            $table->enum('status', ['draft', 'published', 'archived', 'deleted'])
                ->default('draft')
                ->comment('상태');
            $table->enum('visibility', ['public', 'unlisted', 'private'])
                ->default('public')
                ->comment('노출');
            $table->timestamp('published_at')->nullable()->comment('게시일(예약 포함)');

            $table->integer('tags_count')->default(0)->comment('태그 수 캐시(옵션)');
            $table->integer('view_count')->default(0)->comment('조회수 캐시(옵션)');
            $table->integer('like_count')->default(0)->comment('좋아요 캐시(옵션)');

            $table->timestamps();

            $table->index('user_id', 'tips_user_id_idx');
            $table->index('category_id', 'tips_category_id_idx');
            $table->index(['status', 'published_at'], 'tips_status_published_at_idx');
            $table->index('visibility', 'tips_visibility_idx');

            $table->foreign('user_id', 'tips_user_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('category_id', 'tips_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
