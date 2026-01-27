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
        Schema::create('tags', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('name', 50)->comment('표시명');
            $table->string('slug', 60)->unique()->comment('URL용');
            $table->enum('type', ['topic', 'context', 'object'])->nullable()->comment('태그 타입');
            $table->foreignId('normalized_tag_id')
                ->nullable()
                ->comment('동의어/병합용 대표 태그')
                ->constrained('tags')
                ->nullOnDelete();
            $table->integer('usage_count')->comment('사용량 캐시(트렌드 계산용)');
            $table->boolean('is_blocked')->comment('금지 태그');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
