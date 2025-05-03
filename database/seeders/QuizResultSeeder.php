<?php

namespace Database\Seeders;

use App\Models\QuizResult;
use Illuminate\Database\Seeder;

class QuizResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data from the original Next.js application
        $sampleData = json_decode(file_get_contents(database_path('seeders/sample-data.json')), true);
        
        // Create a quiz result record
        QuizResult::create([
            'quiz_id' => $sampleData['data']['id'],
            'user_id' => $sampleData['data']['email'],
            'data' => $sampleData['data'],
        ]);
    }
}
