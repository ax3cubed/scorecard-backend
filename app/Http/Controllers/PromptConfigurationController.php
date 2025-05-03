<?php

namespace App\Http\Controllers;

use App\Models\PromptConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromptConfigurationController extends Controller
{
    /**
     * Get a prompt configuration by section
     */
    public function show($section)
    {
        $config = PromptConfiguration::findBySection($section);

        if (!$config) {
            // Return default configuration if not found
            return response()->json([
                'template' => PromptConfiguration::getDefaultTemplate($section),
                'enabled' => true,
            ]);
        }

        return response()->json([
            'template' => $config->template,
            'enabled' => $config->enabled,
        ]);
    }

    /**
     * Get all prompt configurations
     */
    public function index()
    {
        $configs = PromptConfiguration::all();

        return response()->json($configs);
    }

    /**
     * Store or update a prompt configuration
     */
    public function store(Request $request)
    {
        $request->validate([
            'section' => 'required|string',
            'template' => 'nullable|string',
            'enabled' => 'nullable|boolean',
        ]);

        $section = $request->input('section');
        $template = $request->input('template');
        $enabled = $request->input('enabled');

        // Find existing configuration or create new one
        $config = PromptConfiguration::findBySection($section);

        if (!$config) {
            $config = new PromptConfiguration();
            $config->section = $section;
        }

        // Update fields if provided
        if ($template !== null) {
            $config->template = $template;
        }

        if ($enabled !== null) {
            $config->enabled = $enabled;
        }

        $config->save();

        Log::info('Prompt configuration saved', ['section' => $section]);

        return response()->json([
            'message' => 'Prompt configuration saved successfully',
            'data' => $config,
        ]);
    }

    /**
     * Delete a prompt configuration
     */
    public function destroy($section)
    {
        $config = PromptConfiguration::findBySection($section);

        if (!$config) {
            return response()->json(['error' => 'Prompt configuration not found'], 404);
        }

        $config->delete();

        Log::info('Prompt configuration deleted', ['section' => $section]);

        return response()->json([
            'message' => 'Prompt configuration deleted successfully',
        ]);
    }

    /**
     * Reset a prompt configuration to default
     */
    public function reset($section)
    {
        $config = PromptConfiguration::findBySection($section);

        if (!$config) {
            $config = new PromptConfiguration();
            $config->section = $section;
        }

        $config->template = PromptConfiguration::getDefaultTemplate($section);
        $config->enabled = true;
        $config->save();

        Log::info('Prompt configuration reset to default', ['section' => $section]);

        return response()->json([
            'message' => 'Prompt configuration reset to default',
            'data' => $config,
        ]);
    }
}
