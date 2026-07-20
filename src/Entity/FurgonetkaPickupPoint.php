<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaPickupPointRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Pickup point selected on the checkout select-shipping step.
 *
 * One point per order (the order is the cart during checkout). `shippingMethodCode`
 * records which shipping method the point was chosen for, so the selection can
 * be restored and validated against the method on later steps.
 */
#[ORM\Entity(repositoryClass: FurgonetkaPickupPointRepository::class)]
#[ORM\Table(name: 'furgonetka_pickup_point')]
class FurgonetkaPickupPoint implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: OrderInterface::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?OrderInterface $order = null;

    #[ORM\Column(name: 'shipping_method_code', length: 255)]
    private ?string $shippingMethodCode = null;

    #[ORM\Column(name: 'point_code', length: 100)]
    private ?string $pointCode = null;

    #[ORM\Column(name: 'point_name', length: 255)]
    private ?string $pointName = null;

    #[ORM\Column(name: 'point_type', length: 100)]
    private ?string $pointType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(OrderInterface $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getShippingMethodCode(): ?string
    {
        return $this->shippingMethodCode;
    }

    public function setShippingMethodCode(string $shippingMethodCode): static
    {
        $this->shippingMethodCode = $shippingMethodCode;

        return $this;
    }

    public function getPointCode(): ?string
    {
        return $this->pointCode;
    }

    public function setPointCode(string $pointCode): static
    {
        $this->pointCode = $pointCode;

        return $this;
    }

    public function getPointName(): ?string
    {
        return $this->pointName;
    }

    public function setPointName(string $pointName): static
    {
        $this->pointName = $pointName;

        return $this;
    }

    public function getPointType(): ?string
    {
        return $this->pointType;
    }

    public function setPointType(string $pointType): static
    {
        $this->pointType = $pointType;

        return $this;
    }
}
