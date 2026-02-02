<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('statuses')->upsert(
            [           
                ['name'=>'active', 'description'=>'정상 이용','created_at'=>$now,'updated_at'=>$now],
                ['name'=>'pending', 'description'=>'가입은 됐지만 아직 조건 미충족(이메일 미인증, 약관 미동의 등)','created_at'=>$now,'updated_at'=>$now],
                ['name'=>'suspended', 'description'=>'일시 정지(기간/사유 기반)','created_at'=>$now,'updated_at'=>$now],
                ['name'=>'banned', 'description'=>'영구 차단(재가입/로그인 제한)','created_at'=>$now,'updated_at'=>$now]
            ],
            ['name'],
            ['description','updated_at']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->whereIn('key', ['active','pending','suspended','banned'])->delete();
    }
};
