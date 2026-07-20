<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Controller\Admin;

use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaIntegrationService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaLogsService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FurgonetkaAdminAdvancedController extends AbstractController
{
    public function __construct(
        private readonly FurgonetkaSettingsService $settingsService,
        private readonly FurgonetkaIntegrationService $integrationService,
        private readonly FurgonetkaLogsService $logsService,
    ) {
    }

    public function resetAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('furgonetka_reset', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
        }

        $this->integrationService->disconnectIntegration();
        $this->settingsService->resetSettings(skipMapConfiguration: true);
        $this->logsService->addLog(FurgonetkaLogsLevel::Info, 'Integration disconnected.');

        return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
    }

    public function downloadErrorLogsAction(): JsonResponse
    {
        $logs = $this->logsService->getLogs();

        return new JsonResponse($logs);
    }
}
