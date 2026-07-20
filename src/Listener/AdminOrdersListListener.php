<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Listener;

use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Sylius\Component\Grid\Definition\Action;
use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;

final class AdminOrdersListListener
{
    public function __construct(private readonly FurgonetkaSettingsService $settingsService)
    {
    }

    public function addOrderActionLinks(GridDefinitionConverterEvent $event): void
    {
        $settings = $this->settingsService->getSettings();
        $orderActionLinks = Action::fromNameAndType('order_action_links', 'furgonetka_order_action_links');
        $orderActionLinks->setPosition(1);
        $orderActionLinks->setOptions([
            'furgonetka_is_company_account' => $settings->isCompanyAccount(),
            'visible' => $settings->isPluginIntegrated(),
        ]);

        $grid = $event->getGrid();

        $actionGroupItem = $grid->getActionGroup('item');
        $actionGroupItem->addAction($orderActionLinks);
    }
}
