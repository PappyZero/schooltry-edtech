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
        Schema::table('ai_responses', function (Blueprint $table) {
            $table->renameColumn('response', 'answer');
            $table->renameColumn('recommended_lesson_ids', 'recommended_lessons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_responses', function (Blueprint $table) {
            $table->renameColumn('answer', 'response');
            $table->renameColumn('recommended_lessons', 'recommended_lesson_ids');
        });
    }
};
