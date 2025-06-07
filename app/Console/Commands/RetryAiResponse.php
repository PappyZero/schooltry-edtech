<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAiResponse;
use App\Models\Question;
use Illuminate\Console\Command;

class RetryAiResponse extends Command
{
    protected $signature = 'ai:retry {question_id}';
    protected $description = 'Retry generating AI response for a question';

    public function handle()
    {
        $questionId = $this->argument('question_id');
        $question = Question::findOrFail($questionId);
        
        // Delete existing AI response if it exists
        if ($question->aiResponse) {
            $question->aiResponse()->delete();
            $this->info("Deleted existing AI response for question #$questionId");
        }
        
        // Dispatch the job with just the question ID
        dispatch(new GenerateAiResponse($question->id));
        $this->info("Dispatched job to generate AI response for question #$questionId");
        
        return Command::SUCCESS;
    }
}
