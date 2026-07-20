<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Validator\Constraints;

use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaPickupPointRepository;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaMapService;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PickupPointSelectedValidator extends ConstraintValidator
{
    public function __construct(
        private readonly FurgonetkaPickupPointRepository $pickupPointRepository,
        private readonly FurgonetkaMapService $mapService,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PickupPointSelected) {
            throw new UnexpectedTypeException($constraint, PickupPointSelected::class);
        }

        if (!$value instanceof OrderInterface || !$value->isShippingRequired()) {
            return;
        }

        $shipment = $value->getShipments()->first();
        $method = $shipment === false ? null : $shipment->getMethod();

        // Same mapConfiguration source as the selector/JS: a non-point method is never validated.
        if ($method === null || !$this->mapService->isPickupPointMethod((string) $method->getCode())) {
            return;
        }

        $point = $this->pickupPointRepository->findOneByOrder($value);
        if ($point === null || $point->getShippingMethodCode() !== $method->getCode()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
