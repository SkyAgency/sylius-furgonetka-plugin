<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Controller\Admin;

use Exception;
use Furgonetka\SyliusFurgonetkaPlugin\Exception\ErrorScreenException;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaIntegrationService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FurgonetkaAdminActionController extends AbstractController
{
    private const PAGE_PACKAGE_FORM = 'package_form';
    private const PAGE_QUICK_PACKAGE = 'quick_action';
    private const PAGE_QUICK_INVOICE = 'quick_invoice';

    public function __construct(
        private readonly FurgonetkaSettingsService $settingsService,
        private readonly FurgonetkaIntegrationService $integrationService,
        private readonly string $adminViewsVersion,
    ) {
    }

    /**
     * @throws ErrorScreenException
     */
    public function quickPackageAction(Request $request): Response
    {
        $orderId = (string) $request->attributes->get('orderId');

        return $this->redirect(
            $this->integrationService->createAppUrl(self::PAGE_QUICK_PACKAGE, ['id' => $orderId])
        );
    }

    /**
     * @throws Exception
     */
    public function quickInvoiceAction(Request $request): Response
    {
        $orderId = (string) $request->attributes->get('orderId');

        return $this->redirect(
            $this->integrationService->createAppUrl(self::PAGE_QUICK_INVOICE, ['id' => $orderId])
        );
    }

    /**
     * @throws Exception
     */
    public function packageFormAction(Request $request): Response
    {
        if (!$this->settingsService->getSettings()->isPluginIntegrated()) {
            return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
        }

        $orderId = (string) $request->attributes->get('orderId');

        return $this->render(
            sprintf('@FurgonetkaSyliusFurgonetkaPlugin/admin/%s/iframe_screen.html.twig', $this->adminViewsVersion),
            [
                'pageTitle' => 'sylius.furgonetka.package',
                'appUrl' => $this->integrationService->createAppUrl(self::PAGE_PACKAGE_FORM, ['id' => $orderId]),
            ]
        );
    }
}
