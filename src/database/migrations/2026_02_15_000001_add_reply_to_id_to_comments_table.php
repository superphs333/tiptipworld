<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_id')->nullable()->after('parent_id')->comment('실제 답글 대상 댓글 ID');
            $table->index('reply_to_id', 'comments_reply_to_id_idx');
            $table->foreign('reply_to_id', 'comments_reply_to_id_foreign')
                ->references('id')
                ->on('comments')
                ->nullOnDelete();
        });

        DB::table('comments')
            ->whereNotNull('parent_id')
            ->whereNull('reply_to_id')
            ->update(['reply_to_id' => DB::raw('parent_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign('comments_reply_to_id_foreign');
            $table->dropIndex('comments_reply_to_id_idx');
            $table->dropColumn('reply_to_id');
        });
    }
};
