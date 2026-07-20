<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaLogsRepository;
use Sylius\Component\Resource\Model\ResourceInterface;

#[ORM\Entity(repositoryClass: FurgonetkaLogsRepository::class)]
#[ORM\Table(name: 'furgonetka_logs')]
#[ORM\HasLifecycleCallbacks]
class FurgonetkaLogsEntity implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private int $id;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: FurgonetkaLogsLevel::class)]
    private FurgonetkaLogsLevel $level;

    #[ORM\Column(type: Types::TEXT)]
    private string $data;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTimeImmutable();
        return $this;
    }

    public function getLevel(): FurgonetkaLogsLevel
    {
        return $this->level;
    }

    public function setLevel(FurgonetkaLogsLevel $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'level' => $this->getLevel()->value,
            'data' => json_decode($this->getData(), true) ?? [],
        ];
    }
}
