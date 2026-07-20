<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class-level constraint: when the order's selected shipping method requires a Furgonetka pickup
 * point, the order must have a point saved for that method. Applied in the `sylius_checkout_complete`
 * group so the order cannot be placed without a chosen point.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class PickupPointSelected extends Constraint
{
    public string $message = 'sylius.furgonetka.pickup_point.not_selected';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
