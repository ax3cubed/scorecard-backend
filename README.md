# ScoreApp Laravel Backend

This is the Laravel backend for the ScoreApp AI services. It provides API endpoints for generating insights, recommendations, and general content based on scorecard data.

## Requirements

- PHP 8.1 or higher
- Composer
- Laravel 10
- OpenAI API key
- MySQL or another database supported by Laravel

## Setup

1. Clone the repository
2. Install dependencies:
   
   composer install
   
3. Copy the `.env.example` file to `.env`:
   
   cp .env.example .env
   
4. Generate an application key:
   
   php artisan key:generate
   
5. Set your OpenAI API key in the `.env` file:
   
   OPENAI_API_KEY=your_api_key_here
   
6. Set your frontend URL in the `.env` file:
   
   FRONTEND_URL=http://localhost:3000
   
7. Configure your database connection in the `.env` file
8. Run migrations and seed the database:
   
   php artisan migrate --seed
   
9. Start the development server:
   
   php artisan serve
   

## API Endpoints

### Quiz Results


- GET /api/quiz-results

  - Returns all quiz results.

- GET /api/quiz-results/latest

  - Returns the latest quiz result.

- GET /api/quiz-results/{id}
  - Returns a specific quiz result by ID.

- POST /api/quiz-results
  - Creates a new quiz result or updates an existing one.

Request body:
```json
{
  "event_name": "QUIZ_FINISHED",
  "data": {
    "id": "3d30ade1-e8ad-4334-83c9-ef34a0a20148",
    "status": "Finished",
    "key": "6814d970143e1876465766",
    "first_name": "Steven",
    "last_name": "Oddy",
    "email": "steven@scoreapp.com",
    "total_score": { "percent": 36, "tier": "Low to Average Strength" },
    "category_scores": [
      {
        "denominator10": 2.14,
        "tier": "Low Strength",
        "category": { "id": "641740ed-6120-4a5c-b1f5-1936be8e4ab0", "title": "Profile" }
      }
    ]
  }
}
```

### Insights

## POST /api/insights


Request body:
```json
{
  "data": {
    "total_score": { "percent": 36, "tier": "Low to Average Strength" },
    "category_scores": [
      {
        "denominator10": 2.14,
        "tier": "Low Strength",
        "category": { "id": "641740ed-6120-4a5c-b1f5-1936be8e4ab0", "title": "Profile" }
      }
    ],
    "quiz_questions": [
      {
        "question": "Is it hard for you to write about your business or industry?",
        "answers": [{ "answer": "Yes" }]
      }
    ]
  },
  "type": "strengths"
}
```

Note: If you don't provide the `data` field, the API will use the latest quiz result from the database.

### Recommendations

## POST /api/recommendations


Request body:
```json
{
  "data": {
    "total_score": { "percent": 36, "tier": "Low to Average Strength" },
    "lowest_category": { "id": "a07cd4cc-ab84-4999-9b32-a7d2e73527a8", "title": "Publish" },
    "highest_category": { "id": "2bbcc6bb-c2b4-4ee3-b445-95de98958514", "title": "Product" }
  }
}
```

Note: If you don't provide the `data` field, the API will use the latest quiz result from the database.

### Generate

## POST /api/generate

Request body:
```json
{
  "data": {
    "total_score": { "percent": 36, "tier": "Low to Average Strength" },
    "category_scores": [
      {
        "denominator10": 2.14,
        "tier": "Low Strength",
        "category": { "id": "641740ed-6120-4a5c-b1f5-1936be8e4ab0", "title": "Profile" }
      }
    ],
    "quiz_questions": [
      {
        "question": "Is it hard for you to write about your business or industry?",
        "answers": [{ "answer": "Yes" }]
      }
    ]
  },
  "promptType": "general",
  "promptTemplate": "Custom prompt template (optional)"
}
```

Note: If you don't provide the `data` field, the API will use the latest quiz result from the database.

### Streaming Endpoints


POST /api/stream/insights
POST /api/stream/recommendations


These endpoints accept the same request bodies as their non-streaming counterparts but return Server-Sent Events (SSE) for streaming responses.

## Logging

The application logs information about AI service usage:

- 🟢 Success - OpenAI client initialized or used successfully
- 🟡 Attempt - Attempting to use OpenAI
- 🔴 Error - Error with OpenAI client or API call
- 🔶 Mock - Using mock data
- 🔵 Cache - Using cached response

You can view these logs in the Laravel log file (`storage/logs/laravel.log`).

## Next.js Frontend Integration

To integrate with your Next.js frontend, update your API calls to point to this Laravel backend:

```typescript
// Example: Fetching quiz data
const response = await fetch('http://your-laravel-backend.com/api/quiz-results/latest');
const quizData = await response.json();

// Example: Fetching insights without providing data (uses latest quiz data)
const insightsResponse = await fetch('http://your-laravel-backend.com/api/insights', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ type: 'strengths' }),
});

const insights = await insightsResponse.json();


For streaming responses:

typescript
// Example: Streaming insights without providing data (uses latest quiz data)
const response = await fetch('http://your-laravel-backend.com/api/stream/insights', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'text/event-stream',
  },
  body: JSON.stringify({ type: 'strengths' }),
});

const reader = response.body.getReader();
const decoder = new TextDecoder();

while (true) {
  const { done, value } = await reader.read();
  if (done) break;

  const chunk = decoder.decode(value, { stream: true });
  const lines = chunk.split('\n\n');
  
  for (const line of lines) {
    if (line.startsWith('data: ')) {
      const content = line.substring(6);
      // Process the content
    }
  }
}
```

## Database Structure

The backend stores quiz results in the `quiz_results` table with the following structure:

- `id`: Auto-incrementing primary key
- `quiz_id`: Unique identifier for the quiz (from the original data)
- `user_id`: User identifier (email address)
- `data`: JSON data containing the full quiz result
- `created_at`: Timestamp when the record was created
- `updated_at`: Timestamp when the record was last updated

## Using Quiz Data in Backend Services

The backend services can access quiz data through the `QuizDataService`:

```php
use App\Services\QuizDataService;

class YourController extends Controller
{
    protected $quizDataService;

    public function __construct(QuizDataService $quizDataService)
    {
        $this->quizDataService = $quizDataService;
    }

    public function yourMethod()
    {
        // Get the latest quiz data
        $latestQuizData = $this->quizDataService->getLatestQuizData();
        
        // Or get a specific quiz by ID
        $specificQuizData = $this->quizDataService->getQuizDataById('3d30ade1-e8ad-4334-83c9-ef34a0a20148');
        
        // Use the data as needed
        $totalScore = $latestQuizData['total_score'];
        // ...
    }
}
```

This allows you to use the quiz data throughout your application without having to pass it from the frontend each time.

## Extending the Backend

You can extend this backend by:

1. Adding authentication with Laravel Sanctum
2. Implementing rate limiting for API endpoints
3. Adding more sophisticated caching strategies
4. Creating an admin dashboard for managing quiz data
5. Implementing webhooks to receive quiz data from external sources
