<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin;

use Furgonetka\SyliusFurgonetkaPlugin\DependencyInjection\Compiler\SelectShippingValidationGroupPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class FurgonetkaSyliusFurgonetkaPlugin extends Bundle
{
    use SyliusPluginTrait;

    public const VERSION = '1.0.0';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SelectShippingValidationGroupPass());
    }
}
