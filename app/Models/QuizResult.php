<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quiz_id',
        'user_id',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the full quiz data including the event_name
     */
    public function getFullData()
    {
        return [
            'event_name' => 'QUIZ_FINISHED',
            'data' => $this->data,
        ];
    }

    /**
     * Find a quiz result by its quiz ID
     */
    public static function findByQuizId($quizId)
    {
        return self::where('quiz_id', $quizId)->first();
    }
}
