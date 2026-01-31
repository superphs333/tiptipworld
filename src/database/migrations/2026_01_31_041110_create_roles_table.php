<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->upsert(
            [
                ['key'=>'admin','name'=>'관리자','description'=>'전체 권한','created_at'=>$now,'updated_at'=>$now],
                ['key'=>'editor','name'=>'에디터','description'=>'콘텐츠 편집/관리','created_at'=>$now,'updated_at'=>$now],
                ['key'=>'moderator','name'=>'모더레이터','description'=>'신고/스팸/제재 처리','created_at'=>$now,'updated_at'=>$now],
            ],
            ['key'],
            ['name','description','updated_at']
        );
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('key', ['admin','editor','moderator'])->delete();
    }
};
