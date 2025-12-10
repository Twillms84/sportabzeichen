<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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

    // ---------------------------------------
    // GETTER / SETTER
    // ---------------------------------------

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getKategorie(): string
    {
        return $this->kategorie;
    }

    public function setKategorie(string $kategorie): self
    {
        $this->kategorie = $kategorie;
        return $this;
    }

    public function getEinheit(): string
    {
        return $this->einheit;
    }

    public function setEinheit(string $einheit): self
    {
        $this->einheit = $einheit;
        return $this;
    }

    public function getBerechnungsart(): string
    {
        return $this->berechnungsart;
    }

    public function setBerechnungsart(string $art): self
    {
        $this->berechnungsart = $art;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->kategorie . ')';
    }
}
