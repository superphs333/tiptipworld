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
        // 필드 sort_order 에 nullable 속성
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('sort_order')->nullable()->change();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('sort_order')->nullable(false)->change();
        });
    }
};
