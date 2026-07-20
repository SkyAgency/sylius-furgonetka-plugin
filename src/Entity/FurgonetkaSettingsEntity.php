<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaSettingsRepository;
use Sylius\Component\Resource\Model\ResourceInterface;

#[ORM\Entity(repositoryClass: FurgonetkaSettingsRepository::class)]
#[ORM\Table(name: 'furgonetka_settings')]
class FurgonetkaSettingsEntity implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // LONGTEXT, not VARCHAR: the mapConfiguration JSON can exceed the former 1024 limit.
    #[ORM\Column(type: 'text', length: 4294967295)]
    private ?string $value = null;

    public function getId(): ?string
    {
        return $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
