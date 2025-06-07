<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_generate_response_from_huggingface()
    {
        // Skip if no API key is set
        if (empty(env('HUGGINGFACE_API_KEY'))) {
            $this->markTestSkipped('Hugging Face API key not set');
        }

        $aiService = new AiService();
        
        // Simple test question and content
        $question = 'What is the capital of France?';
        $context = 'France is a country in Europe. Its capital is Paris.';
        
        try {
            $response = $aiService->generateResponse($question, $context);
            
            $this->assertIsString($response, 'Response should be a string');
            $this->assertNotEmpty($response, 'Response should not be empty');
            
            // Check for the expected answer in the response
            $this->assertStringContainsStringIgnoringCase(
                'Paris', 
                $response, 
                'The response should contain the correct answer. ' .
                'Question: ' . $question . ' ' .
                'Context: ' . $context . ' ' .
                'Response: ' . $response
            );
            
        } catch (\Exception $e) {
            $this->fail('Failed to generate response: ' . $e->getMessage() . 
                      '\nQuestion: ' . $question . 
                      '\nContext: ' . $context);
        }
    }
}
