<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaLogsEntity;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaLogDataStructure;

class FurgonetkaLogsRepository extends ServiceEntityRepository
{
    private const QB_ALIAS = 'fl';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FurgonetkaLogsEntity::class);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder(self::QB_ALIAS);
    }

    public function addLog(FurgonetkaLogsLevel $level, FurgonetkaLogDataStructure $data): FurgonetkaLogsEntity
    {
        $log = new FurgonetkaLogsEntity();
        $log->setLevel($level);
        $log->setData($data->toJson());

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush($log);

        return $log;
    }

    public function removeLogsOlderThan(DateTimeImmutable $date): void
    {
        $qb = $this->getQueryBuilder();
        $qb->andWhere(self::QB_ALIAS . '.createdAt < :createdAt')
            ->setParameter('createdAt', $date)
            ->delete();
        $qb->getQuery()->execute();
    }
}
