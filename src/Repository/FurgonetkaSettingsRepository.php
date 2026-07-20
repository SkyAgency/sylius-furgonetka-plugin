<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaSettingsEntity;

class FurgonetkaSettingsRepository extends ServiceEntityRepository
{
    private const QB_ALIAS = 'fs';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FurgonetkaSettingsEntity::class);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder(self::QB_ALIAS);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getSettingByName(string $name): ?FurgonetkaSettingsEntity
    {
        $qb = $this->getQueryBuilder();
        $qb->andWhere(self::QB_ALIAS . '.name = :name')
            ->setParameter('name', $name);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Insert-or-update, keyed on the unique name.
     *
     * @throws NonUniqueResultException
     */
    public function save(string $name, string $value): void
    {
        $existingSetting = $this->getSettingByName($name);
        $setting = $existingSetting ?: new FurgonetkaSettingsEntity();
        $setting->setName($name);
        $setting->setValue($value);
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function removeSetting(string $name): void
    {
        $existingSetting = $this->getSettingByName($name);
        if ($existingSetting) {
            $this->getEntityManager()->remove($existingSetting);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string[] $exceptNames setting names to keep
     */
    public function removeAllSettings(array $exceptNames = []): void
    {
        $qb = $this->getQueryBuilder();

        if ($exceptNames) {
            $qb->andWhere(self::QB_ALIAS . '.name NOT IN (:exceptNames)')
                ->setParameter('exceptNames', $exceptNames);
        }

        $entityManager = $this->getEntityManager();

        foreach ($qb->getQuery()->getResult() as $setting) {
            $entityManager->remove($setting);
        }

        $entityManager->flush();
    }
}
