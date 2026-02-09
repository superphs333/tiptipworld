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
        Schema::table('tips', function (Blueprint $table) {
            $table->unsignedBigInteger('update_user_id')->nullable()->after('user_id')->comment('수정자');
            $table->index('update_user_id', 'tips_update_user_id_idx');
            $table->foreign('update_user_id', 'tips_update_user_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tips', function (Blueprint $table) {
            $table->dropForeign('tips_update_user_id_foreign');
            $table->dropIndex('tips_update_user_id_idx');
            $table->dropColumn('update_user_id');
        });
    }
};
