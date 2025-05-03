<?php

namespace Database\Seeders;

use App\Models\PromptConfiguration;
use Illuminate\Database\Seeder;

class PromptConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = ['general', 'strengths', 'improvements', 'recommendations'];

        foreach ($sections as $section) {
            PromptConfiguration::create([
                'section' => $section,
                'template' => PromptConfiguration::getDefaultTemplate($section),
                'enabled' => true,
            ]);
        }
    }
}
