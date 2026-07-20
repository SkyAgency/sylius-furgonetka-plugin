<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Twig;

use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes Furgonetka integration state to admin templates.
 *
 * Replaces the Sylius 1.x-only ContextProvider mechanism so the same templates
 * work both with sylius_ui events (Sylius 1.14) and Twig Hooks (Sylius 2.x).
 */
final class FurgonetkaAdminExtension extends AbstractExtension
{
    public function __construct(private readonly FurgonetkaSettingsService $settingsService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('furgonetka_is_integrated', [$this, 'isIntegrated']),
            new TwigFunction('furgonetka_is_company_account', [$this, 'isCompanyAccount']),
        ];
    }

    public function isIntegrated(): bool
    {
        return $this->settingsService->getSettings()->isPluginIntegrated();
    }

    public function isCompanyAccount(): bool
    {
        return $this->settingsService->getSettings()->isCompanyAccount();
    }
}
