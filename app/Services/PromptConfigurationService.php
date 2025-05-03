<?php

namespace App\Services;

use App\Models\PromptConfiguration;
use Illuminate\Support\Facades\Log;

class PromptConfigurationService
{
    /**
     * Get the prompt template for a section
     */
    public function getPromptTemplate($section)
    {
        $config = PromptConfiguration::findBySection($section);

        if (!$config) {
            Log::info('Using default prompt template', ['section' => $section]);
            return PromptConfiguration::getDefaultTemplate($section);
        }

        if (!$config->enabled) {
            Log::info('Prompt section is disabled, using default template', ['section' => $section]);
            return PromptConfiguration::getDefaultTemplate($section);
        }

        Log::info('Using custom prompt template', ['section' => $section]);
        return $config->template;
    }

    /**
     * Check if a prompt section is enabled
     */
    public function isPromptEnabled($section)
    {
        $config = PromptConfiguration::findBySection($section);

        if (!$config) {
            return true; // Default to enabled
        }

        return $config->enabled;
    }
}
