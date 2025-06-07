<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\AiResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        // Create regular user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        // Create some lessons
        $lessons = [
            [
                'title' => 'Introduction to Laravel',
                'content' => 'Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:',
                'user_id' => $admin->id,
            ],
            [
                'title' => 'Vue.js Fundamentals',
                'content' => 'Vue.js is a progressive framework for building user interfaces. The core library is focused on the view layer only, and is easy to pick up and integrate with other libraries or existing projects. On the other hand, Vue is also perfectly capable of powering sophisticated Single-Page Applications when used in combination with modern tooling and supporting libraries.',
                'user_id' => $admin->id,
            ],
            [
                'title' => 'Building RESTful APIs',
                'content' => 'REST (Representational State Transfer) is an architectural style that defines a set of constraints to be used for creating web services. RESTful APIs are designed to take advantage of existing protocols. While REST can be used over nearly any protocol, it usually takes advantage of HTTP when used for Web APIs.',
                'user_id' => $admin->id,
            ],
        ];

        foreach ($lessons as $lessonData) {
            $lesson = Lesson::create($lessonData);
            
            // Create some questions for each lesson
            $questions = [
                [
                    'content' => 'What are the main features of ' . $lesson->title . '?',
                    'user_id' => $user->id,
                    'created_at' => now()->subDays(rand(1, 10)),
                ],
                [
                    'content' => 'How do I get started with ' . $lesson->title . '?',
                    'user_id' => $user->id,
                    'created_at' => now()->subDays(rand(1, 10)),
                ],
            ];
            
            foreach ($questions as $questionData) {
                $question = $lesson->questions()->create($questionData);
                
                // Create AI response for each question
                if (rand(0, 1)) { // 50% chance of having an AI response
                    $aiResponse = new AiResponse([
                        'answer' => 'This is a sample AI response for the question about ' . $lesson->title . '. The actual AI would provide a detailed answer here based on the lesson content.',
                        'recommended_lessons' => Lesson::where('id', '!=', $lesson->id)
                            ->inRandomOrder()
                            ->limit(2)
                            ->get()
                            ->map(fn($l) => ['id' => $l->id, 'title' => $l->title])
                            ->toArray(),
                    ]);
                    
                    $question->aiResponse()->save($aiResponse);
                }
            }
        }
        
        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin credentials:');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}
