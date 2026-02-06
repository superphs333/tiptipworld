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
        Schema::create('tip_tag', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedBigInteger('tip_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->primary(['tip_id', 'tag_id']);
            $table->index('tag_id', 'tip_tag_tag_id_index');

            $table->foreign('tip_id', 'tip_tag_tip_id_foreign')
                ->references('id')
                ->on('tips')
                ->restrictOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('tag_id', 'tip_tag_tag_id_foreign')
                ->references('id')
                ->on('tags')
                ->restrictOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tip_tag');
    }
};
