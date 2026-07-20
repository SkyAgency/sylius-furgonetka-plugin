<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Service;

use DateTimeImmutable;
use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaLogsEntity;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaLogsRepository;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaLogDataStructure;

class FurgonetkaLogsService
{
    public function __construct(private readonly FurgonetkaLogsRepository $logsRepository)
    {
    }

    public function addLog(FurgonetkaLogsLevel $level, string $message, array $context = []): void
    {
        $this->removeOldLogs();
        $this->logsRepository->addLog($level, new FurgonetkaLogDataStructure($message, $context));
    }

    public function getLogs(): array
    {
        $logs = $this->logsRepository->findAll();
        return array_map(fn(FurgonetkaLogsEntity $logsEntity) => $logsEntity->toArray(), $logs);
    }

    public function removeOldLogs(): void
    {
        $olderThanDate = new DateTimeImmutable('-14 days');
        $this->logsRepository->removeLogsOlderThan($olderThanDate);
    }

    public static function getErrorLogsFileName(): string
    {
        $now = (new DateTimeImmutable())->format('YmdHis');
        return 'sylius_furgonetka_plugin_error_logs' . $now . '.json';
    }
}
