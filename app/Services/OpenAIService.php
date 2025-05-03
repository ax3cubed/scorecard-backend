<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $apiKey;

    protected $apiUrl;
    protected $model;
    protected $available = true;
    protected $timeout;
    protected $maxTokens = 1000;
    protected $unavailableReason = '';

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->apiUrl = env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
        $this->model = env('OPENAI_MODEL', 'gpt-4o');
        $this->timeout = (int)env('OPENAI_API_TIMEOUT', 30);
        $this->maxTokens = (int)env('OPENAI_API_MAX_TOKENS', 1000);

        // Check if API key is available
        if (!$this->apiKey) {
            $this->available = false;
            $this->unavailableReason = 'OpenAI API key not found';
            Log::warning('🔶 AI SERVICE: OpenAI API key not found');
        }
    }

    /**
     * Check if OpenAI service is available
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * Get the reason why OpenAI is unavailable
     */
    public function getUnavailableReason()
    {
        return $this->unavailableReason;
    }

    /**
     * Generate completion using OpenAI API
     */
    public function generateCompletion($systemPrompt, $userPrompt)
    {
        if (!$this->available) {
            throw new \Exception($this->unavailableReason);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'max_tokens' => $this->maxTokens,
                        'temperature' => 0.7,
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? '';
            } else {
                $this->available = false;
                $this->unavailableReason = 'OpenAI API error: ' . ($response->json()['error']['message'] ?? 'Unknown error');
                Log::error('🔴 AI SERVICE: OpenAI API error', ['response' => $response->json()]);
                throw new \Exception($this->unavailableReason);
            }
        } catch (\Exception $e) {
            $this->available = false;
            $this->unavailableReason = 'OpenAI API request failed: ' . $e->getMessage();
            Log::error('🔴 AI SERVICE: OpenAI API request failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
