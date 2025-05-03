<?php

namespace App\Services;

use App\Models\QuizResult;
use Illuminate\Support\Facades\Log;

class QuizDataService
{
    /**
     * Get the latest quiz result data
     */
    public function getLatestQuizData()
    {
        $quizResult = QuizResult::latest()->first();
        
        if (!$quizResult) {
            Log::warning('No quiz results found in database, using fallback data');
            return $this->getFallbackData();
        }
        
        return $quizResult->data;
    }

    /**
     * Get a quiz result by ID
     */
    public function getQuizDataById($id)
    {
        $quizResult = QuizResult::findByQuizId($id);
        
        if (!$quizResult) {
            Log::warning('Quiz result not found, using fallback data', ['quiz_id' => $id]);
            return $this->getFallbackData();
        }
        
        return $quizResult->data;
    }

    /**
     * Get fallback data when no quiz results are available
     */
    private function getFallbackData()
    {
        // Read from the sample data file
        $sampleData = json_decode(file_get_contents(database_path('seeders/sample-data.json')), true);
        return $sampleData['data'];
    }
}
