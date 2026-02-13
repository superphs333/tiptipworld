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
        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->unsignedBigInteger('tip_id')->comment('댓글 대상 글');
            $table->unsignedBigInteger('user_id')->comment('작성자');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('원댓글 ID (대댓글이면 원댓글 id)');
            $table->unsignedTinyInteger('depth')->default(0)->comment('깊이(0=댓글, 1=대댓글)');
            $table->text('body')->comment('본문(멘션 텍스트 포함 가능)');
            $table->enum('status', ['active', 'deleted', 'hidden'])->default('active')->comment('상태');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요 캐시');
            $table->unsignedInteger('reply_count')->default(0)->comment('대댓글 수 캐시');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('tip_id', 'comments_tip_id_idx');
            $table->index('user_id', 'comments_user_id_idx');
            $table->index('parent_id', 'comments_parent_id_idx');
            $table->index(['tip_id', 'parent_id', 'created_at'], 'comments_tip_parent_created_idx');
            $table->index(['tip_id', 'status', 'created_at'], 'comments_tip_status_created_idx');

            $table->foreign('tip_id', 'comments_tip_id_foreign')
                ->references('id')
                ->on('tips')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'comments_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('parent_id', 'comments_parent_id_foreign')
                ->references('id')
                ->on('comments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
