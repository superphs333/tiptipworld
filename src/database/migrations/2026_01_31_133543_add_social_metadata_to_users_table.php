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
        Schema::table('users', function (Blueprint $table) {
            // json형태를 넣을 컬럼을 추가한다
            $table->text('social_meta')->nullable()->after('provider')->comment('소셜 로그인 메타');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 컬럼삭제
            $table->dropColumn('social_meta');
        });
    }
};
