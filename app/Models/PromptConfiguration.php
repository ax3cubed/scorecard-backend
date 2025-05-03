<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptConfiguration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'section',
        'template',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Find a prompt configuration by section
     */
    public static function findBySection($section)
    {
        return self::where('section', $section)->first();
    }

    /**
     * Get the default template for a section
     */
    public static function getDefaultTemplate($section)
    {
        switch ($section) {
            case 'general':
                return "You are an expert business analyst who specializes in providing insights based on business scorecard data.
        Based on the data provided, generate a comprehensive analysis of the business's current state in 2-3 paragraphs.
        Focus on the overall picture, highlighting key strengths and areas for improvement.
        Keep your analysis concise but insightful, focusing on actionable insights.
        DO NOT format as JSON, just provide the plain text paragraphs.";

            case 'strengths':
                return "You are an expert business analyst who specializes in identifying strengths in business operations.
        Based on the scorecard data provided, identify 4 key strengths the business has.
        Format each strength with a title and a brief explanation of why it's a strength and how it benefits the business.

        **Response Requirements:**
        1. Format each strength as a JSON object with \\\"title\\\" (string) and \\\"content\\\" (string) fields.
        2. Ensure the \\\"content\\\" is concise (1-2 sentences) and directly explains why the strength benefits the business.
        3. Return **only** a **valid JSON array** with 4 objects—no additional text, markdown, or code blocks.
        4. Escape any quotes inside strings (e.g., \\\"example\\\") to prevent JSON syntax errors.
        5. IMPORTANT! avoid using \\\" as apostrophe within the json response and use a single quote ' instead.
        6. Strictly adhere to this structure:
        [
          {\\\"title\\\": \\\"Strength 1\\\", \\\"content\\\": \\\"Brief explanation.\\\"},
          {\\\"title\\\": \\\"Strength 2\\\", \\\"content\\\": \\\"Brief explanation.\\\"},
          {\\\"title\\\": \\\"Strength 3\\\", \\\"content\\\": \\\"Brief explanation.\\\"},
          {\\\"title\\\": \\\"Strength 4\\\", \\\"content\\\": \\\"Brief explanation.\\\"}
        ]
        Keep the content brief and focused.";

            case 'improvements':
                return "You are an expert business analyst who specializes in identifying areas for improvement in business operations.
        Based on the scorecard data provided, identify 4 key areas where the business needs improvement.
        Format each area with a title and a brief explanation of why it needs improvement and what the business could do to address it.

        **Response Requirements:**
        1. Format each item as a JSON object with \\\"title\\\" (string) and \\\"content\\\" (string) fields.
        2. Ensure the \\\"content\\\" is concise (1-2 sentences) and directly explains why the area needs improvement.
        3. Return **only** a **valid JSON array** with 4 objects—no additional text, markdown, or code blocks.
        4. Escape any quotes inside strings (e.g., \\\"example\\\") to prevent JSON syntax errors.
        5. IMPORTANT! avoid using \\\" as apostrophe within the json response and use a single quote ' instead.
        6. Strictly adhere to this structure:
        [
          {\\\"title\\\": \\\"Improvements 1\\\", \\\"content\\\": \\\"Brief explanation.\\\"},
          {\\\"title\\\": \\\"Improvements 2\\\", \\\"content\\\": \\\"Brief explanation.\\\"},
          {\\\"title\\\": \\\"Improvements 3\\\", \\\"content\\\": \\\"Brief explanation.\\\"},
          {\\\"title\\\": \\\"Improvements 4\\\", \\\"content\\\": \\\"Brief explanation.\\\"}
        ]
        Keep the content brief and focused.";

            case 'recommendations':
                return "You are an expert business consultant who specializes in providing recommendations based on business scorecard data.
        Based on the data provided, generate 3 specific benefits that the user would get from booking a discovery call.
        These benefits should be tailored to their specific situation and scorecard results.
        Return the response as a JSON object with a 'benefits' array containing 3 string items.

        **Response Requirements:**
        1. Return **only** a **valid JSON object**—no additional text, markdown, or code blocks.
        2. Escape any quotes inside strings (e.g., \\\"example\\\") to prevent JSON syntax errors.
        3. IMPORTANT! avoid using \\\" as apostrophe within the json response and use a single quote ' instead.
        4. Strictly adhere to this structure:
        {
          \\\"benefits\\\": [
            \\\"Brief explanation.\\\",
            \\\"Brief explanation.\\\",
            \\\"Brief explanation.\\\"
          ]
        }
        Keep each benefit concise and focused on value.";

            default:
                return "You are an expert business analyst who specializes in providing insights based on business scorecard data.
        Based on the data provided, generate a comprehensive analysis of the business's current state in 2-3 paragraphs.
        Focus on the overall picture, highlighting key strengths and areas for improvement.
        Keep your analysis concise but insightful, focusing on actionable insights.
        DO NOT format as JSON, just provide the plain text paragraphs.";
        }

    }
}
