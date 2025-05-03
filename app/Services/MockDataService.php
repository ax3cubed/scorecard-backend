<?php

namespace App\Services;

class MockDataService
{
    /**
     * Get mock data based on type
     */
    public function getMockData($type)
    {
        switch ($type) {
            case 'strengths':
                return json_encode($this->getMockStrengths());
            case 'improvements':
                return json_encode($this->getMockImprovements());
            case 'recommendations':
                return json_encode($this->getMockRecommendations());
            default:
                return $this->getMockInsight();
        }
    }

    /**
     * Get mock strengths data
     */
    public function getMockStrengths()
    {
        return [
            [
                'title' => 'Strong Product Offering',
                'content' => 'Your business has products that sell for more than USD$1500, indicating a valuable offering.',
            ],
            [
                'title' => 'International Presence',
                'content' => 'Operating in several countries gives you a competitive edge in the global market.',
            ],
            [
                'title' => 'Strategic Partnerships',
                'content' => 'You have written agreements with strategic partnerships, creating a solid business network.',
            ],
            [
                'title' => 'Team Development',
                'content' => 'You invest in ongoing training and development for your team, building valuable skills.',
            ],
        ];
    }

    /**
     * Get mock improvements data
     */
    public function getMockImprovements()
    {
        return [
            [
                'title' => 'Value Foundations',
                'content' => 'Your operational processes lack sufficient documentation for team alignment and scaling.',
            ],
            [
                'title' => 'Publishing Strategy',
                'content' => 'You haven\'t written blogs or articles recently, limiting your visibility and authority.',
            ],
            [
                'title' => 'Online Presence',
                'content' => 'Your digital footprint needs improvement with more social media engagement and content.',
            ],
            [
                'title' => 'Business Profitability',
                'content' => 'Focus on making your business profitable after taking a reasonable personal income.',
            ],
        ];
    }

    /**
     * Get mock insight data
     */
    public function getMockInsight()
    {
        return "Your business demonstrates solid strategic foundations with clear goals and vision. However, your operational systems need refinement to scale efficiently, and your marketing approach could benefit from more targeted customer engagement. Your strengths in product development and partnerships provide a strong foundation to build upon, while focusing on improving your publishing and profile strategies could significantly enhance your market position.";
    }

    /**
     * Get mock recommendations data
     */
    public function getMockRecommendations()
    {
        return json_encode([
            'benefits' => [
                'Get tailored advice based on your answers',
                'Learn how others are using ScoreApp to grow faster',
                'No pressure - just a quick 30-min chat with our product team',
            ],
        ]);
    }
}
