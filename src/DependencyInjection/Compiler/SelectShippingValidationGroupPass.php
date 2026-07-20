<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Appends a Furgonetka validation group to Sylius' select-shipping form so the pickup-point
 * constraint runs only there. Reusing the generic `sylius` group would apply it to the
 * cart/address/payment flows too.
 */
final class SelectShippingValidationGroupPass implements CompilerPassInterface
{
    public const VALIDATION_GROUP = 'furgonetka_select_shipping';

    private const PARAMETER = 'sylius.form.type.checkout_select_shipping.validation_groups';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(self::PARAMETER)) {
            return;
        }

        $groups = (array) $container->getParameter(self::PARAMETER);
        if (!in_array(self::VALIDATION_GROUP, $groups, true)) {
            $groups[] = self::VALIDATION_GROUP;
            $container->setParameter(self::PARAMETER, $groups);
        }
    }
}
