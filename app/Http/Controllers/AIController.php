<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAIService;
use App\Services\MockDataService;
use App\Services\QuizDataService;
use App\Services\PromptConfigurationService;

class AIController extends Controller
{
    protected $openAIService;
    protected $mockDataService;
    protected $quizDataService;
    protected $promptConfigService;

    public function __construct(
        OpenAIService $openAIService,
        MockDataService $mockDataService,
        QuizDataService $quizDataService,
        PromptConfigurationService $promptConfigService
    ) {
        $this->openAIService = $openAIService;
        $this->mockDataService = $mockDataService;
        $this->quizDataService = $quizDataService;
        $this->promptConfigService = $promptConfigService;
    }

    /**
     * Generate insights based on scorecard data
     */
    public function insights(Request $request)
    {
        // If data is not provided, use the latest quiz data
        $data = $request->input('data');
        if (!$data) {
            $data = $this->quizDataService->getLatestQuizData();
        }

        $type = $request->input('type', 'general');

        // Validate data
        if (!$data || !isset($data['total_score']) || !isset($data['category_scores'])) {
            return response()->json(['error' => 'Invalid data format'], 400);
        }

        // Check if this prompt type is enabled
        if (!$this->promptConfigService->isPromptEnabled($type)) {
            Log::info('🔶 AI SERVICE: Prompt type is disabled', ['type' => $type]);
            return response()->json(['content' => $this->mockDataService->getMockData($type)]);
        }

        try {
            // Check if OpenAI is available
            if (!$this->openAIService->isAvailable()) {
                Log::info('🔶 AI SERVICE: Using mock data for insights', ['type' => $type, 'reason' => $this->openAIService->getUnavailableReason()]);
                return response()->json(['content' => $this->mockDataService->getMockData($type)]);
            }
            Log::info('🔵 AI SERVICE: Using OpenAI for insights', ['type' => $type]);
            // Prepare the data for the prompt
            $totalScore = $data['total_score'];
            $categoryScores = $data['category_scores'];
            $answers = isset($data['quiz_questions']) ? array_slice($data['quiz_questions'], 0, 15) : [];

            // Format answers for the prompt
            $formattedAnswers = [];
            foreach ($answers as $answer) {
                $formattedAnswers[] = "Q: {$answer['question']}\nA: {$answer['answers'][0]['answer']}";
            }

            // Format category scores for the prompt
            $formattedCategoryScores = [];
            foreach ($categoryScores as $score) {
                $formattedCategoryScores[] = "- {$score['category']['title']}: {$score['denominator10']}/10 ({$score['tier']})";
            }

            // Get the system prompt from the configuration service
            $systemPrompt = $this->getSystemPrompt($type);

            // Create user prompt with the data
            $prompt = "Here is the scorecard data for a business:\n";
            $prompt .= "Total Score: {$totalScore['percent']}/100 ({$totalScore['tier']})\n";
            $prompt .= "Category Scores:\n" . implode("\n", $formattedCategoryScores) . "\n\n";
            $prompt .= "Key Answers:\n" . implode("\n\n", $formattedAnswers);

            Log::info('🟡 AI SERVICE: Attempting to get OpenAI response', ['type' => $type]);

            // Get response from OpenAI
            $content = $this->openAIService->generateCompletion($systemPrompt, $prompt);

            Log::info('🟢 AI SERVICE: Successfully used OpenAI', ['type' => $type]);

            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            Log::error('🔴 AI SERVICE: Error generating insights', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return mock data as fallback
            return response()->json([
                'content' => $this->mockDataService->getMockData($type),
                'note' => 'This is a fallback response due to an error.'
            ]);
        }
    }

    /**
     * Generate recommendations based on scorecard data
     */
    public function recommendations(Request $request)
    {
        // If data is not provided, use the latest quiz data
        $data = $request->input('data');
        if (!$data) {
            $data = $this->quizDataService->getLatestQuizData();
        }

        // Validate request
        if (!$data || !isset($data['total_score']) || !isset($data['lowest_category']) || !isset($data['highest_category'])) {
            return response()->json(['error' => 'Invalid data format'], 400);
        }

        // Check if recommendations prompt is enabled
        if (!$this->promptConfigService->isPromptEnabled('recommendations')) {
            Log::info('🔶 AI SERVICE: Recommendations prompt is disabled');
            return response()->json(['content' => $this->mockDataService->getMockData('recommendations')]);
        }

        try {
            // Check if OpenAI is available
            if (!$this->openAIService->isAvailable()) {
                Log::info('🔶 AI SERVICE: Using mock data for recommendations', ['reason' => $this->openAIService->getUnavailableReason()]);
                return response()->json(['content' => $this->mockDataService->getMockData('recommendations')]);
            }

            Log::info('🔵 AI SERVICE: Using OpenAI for recommendations');
            // Prepare the data for the prompt
            $totalScore = $data['total_score'];
            $lowestCategory = $data['lowest_category'];
            $highestCategory = $data['highest_category'];

            // Get the system prompt from the configuration service
            $systemPrompt = $this->promptConfigService->getPromptTemplate('recommendations');

            // Create user prompt with the data
            $prompt = "Here is the scorecard data for a business:\n";
            $prompt .= "Total Score: {$totalScore['percent']}/100 ({$totalScore['tier']})\n";
            $prompt .= "Lowest Scoring Category: {$lowestCategory['title']}\n";
            $prompt .= "Highest Scoring Category: {$highestCategory['title']}\n\n";
            $prompt .= "Generate 3 specific benefits that this business would get from booking a discovery call with our business consultants.";

            Log::info('🟡 AI SERVICE: Attempting to get OpenAI response for recommendations');

            // Get response from OpenAI
            $content = $this->openAIService->generateCompletion($systemPrompt, $prompt);

            Log::info('🟢 AI SERVICE: Successfully used OpenAI for recommendations');

            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            Log::error('🔴 AI SERVICE: Error generating recommendations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return mock data as fallback
            return response()->json([
                'content' => $this->mockDataService->getMockData('recommendations'),
                'note' => 'This is a fallback response due to an error.'
            ]);
        }
    }

    /**
     * Generate general content based on scorecard data and prompt template
     */
    public function generate(Request $request)
    {
        // If data is not provided, use the latest quiz data
        $data = $request->input('data');
        if (!$data) {
            $data = $this->quizDataService->getLatestQuizData();
        }

        $promptType = $request->input('promptType', 'general');
        $promptTemplate = $request->input('promptTemplate');

        // Validate request
        if (!$data || !isset($data['total_score']) || !isset($data['category_scores'])) {
            return response()->json(['error' => 'Invalid data format'], 400);
        }

        // Check if this prompt type is enabled
        if (!$this->promptConfigService->isPromptEnabled($promptType)) {
            Log::info('🔶 AI SERVICE: Prompt type is disabled', ['type' => $promptType]);
            return response()->json([
                'content' => "This insight type is currently disabled.",
                'note' => "The administrator has disabled this insight type."
            ]);
        }



        // Create cache key
        $cacheKey = md5(json_encode($data) . '-' . $promptType . '-' . $promptTemplate);

        // Check if we have a cached response
        if (\Cache::has($cacheKey)) {
            Log::info('🔵 AI SERVICE: Using cached response', ['type' => $promptType]);
            return response()->json(\Cache::get($cacheKey));
        }

        try {
            // Default system prompt
            $systemPrompt = $this->promptConfigService->getPromptTemplate('general');

            // Use custom prompt template if provided
            if ($promptTemplate) {
                $systemPrompt = $promptTemplate;
            } else {
                // Otherwise use default prompts based on type
                $systemPrompt = $this->promptConfigService->getPromptTemplate($promptType);
            }

            // Prepare the data for the prompt
            $totalScore = $data['total_score'];
            $categoryScores = $data['category_scores'];
            $answers = isset($data['quiz_questions']) ? array_slice($data['quiz_questions'], 0, 15) : [];

            // Format answers for the prompt
            $formattedAnswers = [];
            foreach ($answers as $answer) {
                $formattedAnswers[] = "Q: {$answer['question']}\nA: {$answer['answers'][0]['answer']}";
            }

            // Format category scores for the prompt
            $formattedCategoryScores = [];
            foreach ($categoryScores as $score) {
                $formattedCategoryScores[] = "- {$score['category']['title']}: {$score['denominator10']}/10 ({$score['tier']})";
            }

            // Create user prompt with the data
            $prompt = "Here is the scorecard data for a business:\n";
            $prompt .= "Total Score: {$totalScore['percent']}/100 ({$totalScore['tier']})\n";
            $prompt .= "Category Scores:\n" . implode("\n", $formattedCategoryScores) . "\n\n";
            $prompt .= "Key Answers:\n" . implode("\n\n", $formattedAnswers);

            $response = [];

            // Check if OpenAI is available
            if (!$this->openAIService->isAvailable()) {
                Log::info('🔶 AI SERVICE: Using mock data for generate', [
                    'type' => $promptType,
                    'reason' => $this->openAIService->getUnavailableReason()
                ]);


                $response = [
                    'content' => "We analyzed your business scorecard and found several key insights. Your strengths include product development and international presence, while areas for improvement include publishing strategy and online presence. Consider focusing on building your authority through content creation and optimizing your digital footprint for better visibility.",
                    'note' => "This is a fallback response. Reason: " . $this->openAIService->getUnavailableReason()
                ];
            } else {
                try {
                    Log::info('🟡 AI SERVICE: Attempting to get OpenAI response', ['type' => $promptType]);

                    // Get response from OpenAI
                    $content = $this->openAIService->generateCompletion($systemPrompt, $prompt);

                    Log::info('🟢 AI SERVICE: Successfully used OpenAI', ['type' => $promptType]);

                    $response = ['content' => $content];
                } catch (\Exception $e) {
                    Log::error('🔴 AI SERVICE: Error generating content', [
                        'type' => $promptType,
                        'error' => $e->getMessage()
                    ]);

                    $response = [
                        'content' => "We analyzed your business scorecard and found several key insights. Your strengths include product development and international presence, while areas for improvement include publishing strategy and online presence. Consider focusing on building your authority through content creation and optimizing your digital footprint for better visibility.",
                        'note' => "This is a fallback response due to an error connecting to our AI service."
                    ];
                }
            }

            // Cache the response for 24 hours
            \Cache::put($cacheKey, $response, 60 * 24);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('🔴 AI SERVICE: Error in generate endpoint', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to generate content'], 500);
        }
    }

    /**
     * Get system prompt based on insight type
     */
    private function getSystemPrompt($type)
    {
        return $this->promptConfigService->getPromptTemplate($type);
    }
}
