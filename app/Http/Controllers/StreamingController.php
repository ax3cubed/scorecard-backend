<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAIService;
use App\Services\MockDataService;
use App\Services\QuizDataService;
use App\Services\PromptConfigurationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamingController extends Controller
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
     * Stream insights based on scorecard data
     */
    public function streamInsights(Request $request)
    {
        // If data is not provided, use the latest quiz data
        $data = $request->input('data');
        if (!$data) {
            $data = $this->quizDataService->getLatestQuizData();
        }

        $type = $request->input('type', 'general');

        // Validate request
        if (!$data || !isset($data['total_score']) || !isset($data['category_scores'])) {
            return response()->json(['error' => 'Invalid data format'], 400);
        }

        // Check if this prompt type is enabled
        if (!$this->promptConfigService->isPromptEnabled($type)) {
            Log::info('🔶 AI SERVICE: Prompt type is disabled for streaming', ['type' => $type]);
            return response()->json(['error' => 'This insight type is currently disabled'], 403);
        }

        // Create a streamed response
        $response = new StreamedResponse(function () use ($data, $type) {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable buffering for Nginx

            // Check if OpenAI is available
            if (!$this->openAIService->isAvailable()) {
                Log::info('🔶 AI SERVICE: Using mock data for streaming', [
                    'type' => $type,
                    'reason' => $this->openAIService->getUnavailableReason()
                ]);

                // Send mock data as a single event
                echo "data: " . $this->mockDataService->getMockData($type) . "\n\n";
                flush();
                return;
            }

            try {
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
                $systemPrompt = $this->promptConfigService->getPromptTemplate($type);

                // Create user prompt with the data
                $prompt = "Here is the scorecard data for a business:\n";
                $prompt .= "Total Score: {$totalScore['percent']}/100 ({$totalScore['tier']})\n";
                $prompt .= "Category Scores:\n" . implode("\n", $formattedCategoryScores) . "\n\n";
                $prompt .= "Key Answers:\n" . implode("\n\n", $formattedAnswers);

                Log::info('🟡 AI SERVICE: Attempting to stream OpenAI response', ['type' => $type]);

                // This is a simplified implementation since true streaming requires
                // a more complex setup with OpenAI's API
                // In a real implementation, you would use a library that supports streaming
                // or implement a custom solution using curl or similar

                // For now, we'll simulate streaming by sending the full response
                $content = $this->openAIService->generateCompletion($systemPrompt, $prompt);

                // Break the content into chunks to simulate streaming
                $chunks = str_split($content, 20);
                foreach ($chunks as $chunk) {
                    echo "data: " . $chunk . "\n\n";
                    flush();
                    usleep(100000); // Sleep for 100ms to simulate streaming
                }

                Log::info('🟢 AI SERVICE: Successfully streamed OpenAI response', ['type' => $type]);
            } catch (\Exception $e) {
                Log::error('🔴 AI SERVICE: Error streaming insights', [
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);

                // Send mock data as fallback
                echo "data: " . $this->mockDataService->getMockData($type) . "\n\n";
                flush();
            }
        });

        // Set headers for SSE
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable buffering for Nginx

        // Set CORS headers
        $response->headers->set('Access-Control-Allow-Origin', env('FRONTEND_URL', '*'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }

    /**
     * Stream recommendations based on scorecard data
     */
    public function streamRecommendations(Request $request)
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
            Log::info('🔶 AI SERVICE: Recommendations prompt is disabled for streaming');
            return response()->json(['error' => 'Recommendations are currently disabled'], 403);
        }

        // Create a streamed response
        $response = new StreamedResponse(function () use ($data) {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable buffering for Nginx

            // Check if OpenAI is available
            if (!$this->openAIService->isAvailable()) {
                Log::info('🔶 AI SERVICE: Using mock data for streaming recommendations', [
                    'reason' => $this->openAIService->getUnavailableReason()
                ]);

                // Send mock data as a single event
                echo "data: " . $this->mockDataService->getMockData('recommendations') . "\n\n";
                flush();
                return;
            }

            try {
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

                Log::info('🟡 AI SERVICE: Attempting to stream OpenAI response for recommendations');

                // Get response from OpenAI
                $content = $this->openAIService->generateCompletion($systemPrompt, $prompt);

                // Break the content into chunks to simulate streaming
                $chunks = str_split($content, 20);
                foreach ($chunks as $chunk) {
                    echo "data: " . $chunk . "\n\n";
                    flush();
                    usleep(100000); // Sleep for 100ms to simulate streaming
                }

                Log::info('🟢 AI SERVICE: Successfully streamed OpenAI response for recommendations');
            } catch (\Exception $e) {
                Log::error('🔴 AI SERVICE: Error streaming recommendations', [
                    'error' => $e->getMessage()
                ]);

                // Send mock data as fallback
                echo "data: " . $this->mockDataService->getMockData('recommendations') . "\n\n";
                flush();
            }
        });

        // Set headers for SSE
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable buffering for Nginx

        // Set CORS headers
        $response->headers->set('Access-Control-Allow-Origin', env('FRONTEND_URL', '*'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
