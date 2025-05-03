<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuizResultController extends Controller
{
    /**
     * Get a quiz result by ID
     */
    public function show($id)
    {
        $quizResult = QuizResult::findByQuizId($id);

        if (!$quizResult) {
            return response()->json(['error' => 'Quiz result not found'], 404);
        }

        return response()->json($quizResult->getFullData());
    }

    /**
     * Get the latest quiz result
     */
    public function latest()
    {
        $quizResult = QuizResult::latest()->first();

        if (!$quizResult) {
            return response()->json(['error' => 'No quiz results found'], 404);
        }

        return response()->json($quizResult->getFullData());
    }

    /**
     * Get all quiz results
     */
    public function index()
    {
        $quizResults = QuizResult::all();

        $formattedResults = $quizResults->map(function ($result) {
            return $result->getFullData();
        });

        return response()->json($formattedResults);
    }

    /**
     * Store a new quiz result
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string',
            'data' => 'required|array',
            'data.id' => 'required|string',
        ]);

        $quizId = $request->input('data.id');
        $userId = $request->input('data.email');
        $data = $request->input('data');

        // Check if quiz result already exists
        $existingResult = QuizResult::findByQuizId($quizId);

        if ($existingResult) {
            // Update existing result
            $existingResult->update([
                'user_id' => $userId,
                'data' => $data,
            ]);

            Log::info('Quiz result updated', ['quiz_id' => $quizId]);

            return response()->json([
                'message' => 'Quiz result updated successfully',
                'data' => $existingResult->getFullData(),
            ]);
        }

        // Create new quiz result
        $quizResult = QuizResult::create([
            'quiz_id' => $quizId,
            'user_id' => $userId,
            'data' => $data,
        ]);

        Log::info('Quiz result created', ['quiz_id' => $quizId]);

        return response()->json([
            'message' => 'Quiz result created successfully',
            'data' => $quizResult->getFullData(),
        ], 201);
    }
}
