<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\AiResponse;
use Illuminate\Console\Command;

class ClearQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all questions and AI responses';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->confirm('Are you sure you want to delete all questions and AI responses? This cannot be undone.')) {
            // Delete all AI responses first due to foreign key constraints
            $deletedResponses = AiResponse::query()->delete();
            
            // Then delete all questions
            $deletedQuestions = Question::query()->delete();
            
            $this->info("Successfully deleted $deletedQuestions questions and $deletedResponses AI responses.");
            
            // Clear any queued jobs
            $this->call('queue:clear', [
                '--queue' => 'default',
                '--force' => true,
            ]);
            
            $this->info('Cleared all queued jobs.');
            
            return Command::SUCCESS;
        }
        
        $this->info('Operation cancelled.');
        return Command::SUCCESS;
    }
}
