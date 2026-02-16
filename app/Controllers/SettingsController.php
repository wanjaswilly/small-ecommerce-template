<?php
# app/Controllers/SettingsController.php

namespace App\Controllers;

use App\Services\SettingsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Setting;
use Illuminate\Database\Capsule\Manager as DB;

class SettingsController extends BaseController
{

    private SettingsService $settingsService;
    /**
     * Show settings page
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->render($request, $response, 'admin/settings.twig', $this->settingsService->allSettings());
    }

    /**
     * Update settings
     */
    public function update(Request $request, Response $response): Response
    {
        $this->settingsService->updateAllSettings($request);
        return $this->index($request, $response);
    }

    /**
     * API: Get settings (for AJAX requests)
     */
    public function getSettings(Request $request, Response $response): Response
    {
        return $this->json($response, $this->settingsService->getSettings($request));
    }

    /**
     * API: Update specific setting
     */
    public function updateSetting(Request $request, Response $response): Response
    {
        return $this->json($response, $this->settingsService->updateSetting($request));
    }

    /**
     * Reset settings to defaults
     */
    public function reset(Request $request, Response $response): Response
    {
        $this->settingsService->reset();
        return $this->redirect($response, '/admin/settings');

    }
}
