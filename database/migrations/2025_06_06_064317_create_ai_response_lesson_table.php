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
        if (!Schema::hasTable('ai_response_lesson')) {
            Schema::create('ai_response_lesson', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_response_id')->constrained('ai_responses')->onDelete('cascade');
                $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
                $table->integer('relevance_score')->default(0);
                $table->timestamps();
                
                // Add unique constraint to prevent duplicate relationships
                $table->unique(['ai_response_id', 'lesson_id']);
            });
        } else {
            // Table already exists, make sure it has the correct structure
            Schema::table('ai_response_lesson', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_response_lesson', 'relevance_score')) {
                    $table->integer('relevance_score')->default(0)->after('lesson_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_response_lesson');
    }
};
