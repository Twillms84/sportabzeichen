<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

#[ORM\Entity]
#[ORM\Table(name: 'sportabzeichen_disciplines')]
class SportabzeichenDiscipline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'text')]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $kategorie;

    #[ORM\Column(type: 'text')]
    private string $einheit;

    #[ORM\Column(type: 'text')]
    private string $berechnungsart;

    #[ORM\Column(type: 'datetimetz')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
