<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Controller\Admin;

use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaIntegrationService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaLogsService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FurgonetkaAdminPageController extends AbstractController
{
    public const PAGE_PLUGIN_PANEL = 'panel_settings';
    public const PAGE_WAITING_PACKAGES = 'waiting_packages';
    public const PAGE_ORDERED_PACKAGES = 'ordered_packages';
    public const PAGE_RETURNS = 'returns';
    public const PAGE_ORDERS_RETURNS = 'orders_returns';
    public const PAGE_MAP_SETTINGS = 'map_settings';
    public const PAGE_APPEARANCE = 'appearance';
    public const PAGE_ADDRESS_BOOK = 'address_book';
    public const PAGE_PACKAGE_TEMPLATES = 'package_templates';
    public const PAGE_PRINTING = 'printing';
    public const PAGE_NOTIFICATIONS = 'notifications';
    public const PAGE_OWN_AGREEMENTS = 'own_agreements';
    public const PAGE_ADVANCED = 'advanced';
    public const PAGE_ACCOUNT_DETAILS = 'account_details';
    public const PAGE_HELP_AND_CONTACT = 'help_and_contact';
    public const PAGE_TERMS_AND_CONDITIONS = 'terms_and_conditions';
    public const PAGE_BALANCE = 'balance';
    public const PAGE_MY_PRICE_LISTS = 'my_price_lists';
    public const PAGE_INVOICES = 'invoices_and_financial_documents';
    public const PAGE_LIST_OF_TRANSFERS = 'list_of_transfers';
    public const PAGE_COMPLAINTS = 'complaints';

    public const PAGE_ADVANCED_SETTINGS = 'advanced_settings';

    /** Pages rendered locally instead of via the Furgonetka app iframe. */
    private const LOCAL_PAGES = [self::PAGE_ADVANCED_SETTINGS];

    /**
     * Single source of truth for the admin menu.
     * Each inner array is a section (rendered flat, divider-separated, by AdminMenuListener);
     * shape: page slug => company-account-only. The iframe-page whitelist is derived from this
     * (every page except LOCAL_PAGES), so a new menu item only has to be added here.
     */
    public const MENU_SECTIONS = [
        [
            self::PAGE_PLUGIN_PANEL => false,
            self::PAGE_WAITING_PACKAGES => false,
            self::PAGE_ORDERED_PACKAGES => false,
            self::PAGE_RETURNS => false,
        ],
        [
            self::PAGE_ORDERS_RETURNS => false,
            self::PAGE_MAP_SETTINGS => false,
            self::PAGE_APPEARANCE => false,
            self::PAGE_ADVANCED_SETTINGS => false,
        ],
        [
            self::PAGE_ADDRESS_BOOK => false,
            self::PAGE_PACKAGE_TEMPLATES => false,
            self::PAGE_PRINTING => false,
            self::PAGE_NOTIFICATIONS => false,
            self::PAGE_OWN_AGREEMENTS => false,
            self::PAGE_ADVANCED => false,
        ],
        [
            self::PAGE_ACCOUNT_DETAILS => false,
            self::PAGE_HELP_AND_CONTACT => false,
            self::PAGE_TERMS_AND_CONDITIONS => false,
        ],
        [
            self::PAGE_BALANCE => false,
            self::PAGE_MY_PRICE_LISTS => false,
            self::PAGE_INVOICES => true,
            self::PAGE_LIST_OF_TRANSFERS => false,
            self::PAGE_COMPLAINTS => false,
        ],
    ];

    public function __construct(
        private readonly FurgonetkaIntegrationService $integrationService,
        private readonly FurgonetkaSettingsService $settingsService,
        private readonly string $adminViewsVersion,
    ) {
    }

    private function template(string $name): string
    {
        return sprintf('@FurgonetkaSyliusFurgonetkaPlugin/admin/%s/%s', $this->adminViewsVersion, $name);
    }

    public function pageAction(Request $request): Response
    {
        if (!$this->settingsService->getSettings()->isPluginIntegrated()) {
            return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
        }

        $page = $request->attributes->get('page');

        if ($page === self::PAGE_ADVANCED_SETTINGS) {
            return $this->render($this->template('advanced.html.twig'), [
                'pageTitle' => 'sylius.furgonetka.menu.' . self::PAGE_ADVANCED_SETTINGS,
                'resetUrl' => $this->generateUrl('furgonetka_sylius_furgonetka_plugin_admin_advanced_reset'),
                'downloadErrorLogsUrl' => $this->generateUrl('furgonetka_sylius_furgonetka_plugin_admin_advanced_download_error_logs'),
                'errorLogsFileName' => FurgonetkaLogsService::getErrorLogsFileName(),
            ]);
        }

        if ($this->isIframePage($page)) {
            return $this->render($this->template('iframe_screen.html.twig'), [
                'pageTitle' => 'sylius.furgonetka.menu.' . $page,
                'appUrl' => $this->integrationService->createAppUrl($page),
            ]);
        }

        return $this->redirectToRoute(
            'furgonetka_sylius_furgonetka_plugin_admin_page',
            ['page' => self::PAGE_PLUGIN_PANEL]
        );
    }

    private function isIframePage(mixed $page): bool
    {
        if (!is_string($page) || in_array($page, self::LOCAL_PAGES, true)) {
            return false;
        }

        foreach (self::MENU_SECTIONS as $section) {
            if (array_key_exists($page, $section)) {
                return true;
            }
        }

        return false;
    }

    public function beginPageAction(Request $request): Response
    {
        if ($this->settingsService->getSettings()->isPluginIntegrated()) {
            return $this->redirectToRoute(
                'furgonetka_sylius_furgonetka_plugin_admin_page',
                ['page' => FurgonetkaAdminPageController::PAGE_PLUGIN_PANEL]
            );
        }

        return $this->render($this->template('welcome_screen.html.twig'), [
            'pageTitle' => 'sylius.furgonetka.begin',
            'baseShopUrl' => FurgonetkaIntegrationService::getBaseShopUrl(),
            'welcomeUrl' => sprintf(
                '%s/ecommerce/app/welcome_screen?origin=%s&type=%s',
                FurgonetkaIntegrationService::getBaseShopUrl(),
                $request->getSchemeAndHttpHost(),
                FurgonetkaIntegrationService::INTEGRATION_TYPE,
            ),
            'connectUrl' => $this->generateUrl('furgonetka_sylius_furgonetka_plugin_admin_integration_connect'),
        ]);
    }

    public function errorPageAction(Request $request): Response
    {
        $errorCode = $request->attributes->get('errorCode');

        return $this->render($this->template('error_screen.html.twig'), [
            'pageTitle' => 'sylius.furgonetka.furgonetka',
            'baseShopUrl' => FurgonetkaIntegrationService::getBaseShopUrl(),
            'errorScreenUrl' => $this->integrationService->createErrorUrl($errorCode, $request->getSchemeAndHttpHost()),
            'redirectUrl' => $this->generateUrl(
                'furgonetka_sylius_furgonetka_plugin_admin_page',
                ['page' => FurgonetkaAdminPageController::PAGE_PLUGIN_PANEL]
            ),
        ]);
    }
}
