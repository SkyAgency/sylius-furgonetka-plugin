<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Listener;

use Furgonetka\SyliusFurgonetkaPlugin\Controller\Admin\FurgonetkaAdminPageController as Page;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaAuthService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AdminMenuListener
{
    private const MENU_ROUTE = 'furgonetka_sylius_furgonetka_plugin_admin_page';

    /** Marks the group so CSS can paint the Furgonetka brand icon on it (both Sylius versions). */
    private const GROUP_CLASS = 'furgonetka-menu-group';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly FurgonetkaSettingsService $settingsService,
        private readonly FurgonetkaAuthService $authService,
    ) {
    }

    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $furgonetkaSubmenu = $event->getMenu()
            ->addChild('furgonetka')
            ->setLabel($this->translator->trans('sylius.furgonetka.furgonetka', [], 'furgonetka'))
            ->setAttribute('class', self::GROUP_CLASS);

        if (!$this->settingsService->getSettings()->isPluginIntegrated()) {
            $furgonetkaSubmenu
                ->addChild('furgonetka-begin', ['route' => 'furgonetka_sylius_furgonetka_plugin_admin_page_begin'])
                ->setLabel($this->translator->trans('sylius.furgonetka.begin', [], 'furgonetka'));

            return;
        }

        $this->buildIntegratedMenu($furgonetkaSubmenu);

        // Try to refresh token
        $this->authService->refreshToken();
    }

    private function buildIntegratedMenu(ItemInterface $submenu): void
    {
        $isCompanyAccount = $this->settingsService->getSettings()->isCompanyAccount();

        foreach (Page::MENU_SECTIONS as $sectionIndex => $items) {
            if ($sectionIndex > 0) {
                $this->addSeparator($submenu, $sectionIndex);
            }

            foreach ($items as $page => $companyOnly) {
                if ($companyOnly && !$isCompanyAccount) {
                    continue;
                }

                $submenu
                    ->addChild('furgonetka-' . $page, [
                        'route' => self::MENU_ROUTE,
                        'routeParameters' => ['page' => $page],
                    ])
                    ->setLabel($this->translator->trans('sylius.furgonetka.menu.' . $page, [], 'furgonetka'));
            }
        }
    }

    /**
     * Sylius has no native menu divider, so sections are split by a plain "-" item.
     * setCurrent(false): it shares the plugin-panel route, so without it the matcher would
     * highlight every separator when that page is open.
     */
    private function addSeparator(ItemInterface $submenu, int $index): void
    {
        $submenu
            ->addChild('furgonetka-separator-' . $index, [
                'route' => self::MENU_ROUTE,
                'routeParameters' => ['page' => Page::PAGE_PLUGIN_PANEL],
            ])
            ->setLabel('-')
            ->setCurrent(false);
    }
}
