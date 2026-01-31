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
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            
            // 중복 부여 방지 
            $table->unique(['role_id', 'user_id']);

            // 조회 최적화
            $table->index(['user_id']);
            $table->index(['role_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
