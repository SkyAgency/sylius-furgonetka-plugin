<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Form\Extension;

use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaMapService;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\SelectShippingType;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * On select-shipping submit, drops a saved point that no longer matches the chosen method.
 * The frontend also clears it over AJAX, but that races with the submit; this is the reliable
 * server-side fallback. Removal logic lives in FurgonetkaMapService.
 */
final class SelectShippingTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly FurgonetkaMapService $mapService,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [SelectShippingType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->clearStalePickupPoint(...));
    }

    private function clearStalePickupPoint(FormEvent $event): void
    {
        $order = $event->getData();
        if ($order instanceof OrderInterface) {
            $this->mapService->clearStalePickupPoint($order);
        }
    }
}
