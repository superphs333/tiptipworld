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
            // 1. 기존 google_id를 social_id로 이름 변경
            $table->renameColumn('google_id', 'social_id');

            // 2. 가입 방식(provider) 컬럼 추가 (기본값: email)
            $table->string('provider', 20)->default('email')->after('password')->comment('가입 방식 (email, google, kakao 등)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('provider');
            $table->renameColumn('social_id', 'google_id');
        });
    }
};
