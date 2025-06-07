<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('ai_responses', function (Blueprint $table) {
            // Add answer column if it doesn't exist
            if (!Schema::hasColumn('ai_responses', 'answer')) {
                $table->text('answer')->after('id');
            }

            // Rename recommended_lesson_ids to recommended_lessons if it exists
            if (Schema::hasColumn('ai_responses', 'recommended_lesson_ids')) {
                $table->renameColumn('recommended_lesson_ids', 'recommended_lessons');
            } elseif (!Schema::hasColumn('ai_responses', 'recommended_lessons')) {
                $table->json('recommended_lessons')->nullable()->after('answer');
            }

            // Drop the old response column if it exists
            if (Schema::hasColumn('ai_responses', 'response')) {
                $table->dropColumn('response');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('ai_responses', function (Blueprint $table) {
            if (Schema::hasColumn('ai_responses', 'answer')) {
                $table->dropColumn('answer');
            }

            if (Schema::hasColumn('ai_responses', 'recommended_lessons')) {
                $table->renameColumn('recommended_lessons', 'recommended_lesson_ids');
            }

            if (!Schema::hasColumn('ai_responses', 'response')) {
                $table->text('response');
            }
        });
    }
};
