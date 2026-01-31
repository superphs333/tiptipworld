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
        // user 테이블의  기존 컬럼 point의 기본값 0으로 셋팅
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('point')->default(0)->change();
        });
      
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
