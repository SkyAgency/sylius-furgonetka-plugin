<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaPickupPoint;
use Sylius\Component\Core\Model\OrderInterface;

class FurgonetkaPickupPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FurgonetkaPickupPoint::class);
    }

    public function findOneByOrder(OrderInterface $order): ?FurgonetkaPickupPoint
    {
        return $this->findOneBy(['order' => $order]);
    }

    public function save(FurgonetkaPickupPoint $pickupPoint): void
    {
        $this->getEntityManager()->persist($pickupPoint);
        $this->getEntityManager()->flush();
    }

    public function remove(FurgonetkaPickupPoint $pickupPoint): void
    {
        $this->getEntityManager()->remove($pickupPoint);
        $this->getEntityManager()->flush();
    }
}
