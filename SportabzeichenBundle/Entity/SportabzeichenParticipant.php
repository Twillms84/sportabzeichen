<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sportabzeichen_participants')]
class SportabzeichenParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'text', unique: true)]
    private string $importId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $vorname = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nachname = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $geschlecht = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $geburtsdatum = null;

    #[ORM\Column(type: 'datetimetz')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // GETTER & SETTER …

    public function getId(): int { return $this->id; }

    public function getImportId(): string { return $this->importId; }
    public function setImportId(string $id): self { $this->importId = $id; return $this; }

    public function getVorname(): ?string { return $this->vorname; }
    public function setVorname(?string $v): self { $this->vorname = $v; return $this; }

    public function getNachname(): ?string { return $this->nachname; }
    public function setNachname(?string $n): self { $this->nachname = $n; return $this; }

    public function getGeschlecht(): ?string { return $this->geschlecht; }
    public function setGeschlecht(?string $g): self { $this->geschlecht = $g; return $this; }

    public function getGeburtsdatum(): ?\DateTimeInterface { return $this->geburtsdatum; }
    public function setGeburtsdatum(?\DateTimeInterface $d): self { $this->geburtsdatum = $d; return $this; }

    public function getAgeInYear(int $year): ?int
    {
        if (!$this->geburtsdatum) return null;
        return $year - (int)$this->geburtsdatum->format('Y');
    }
}
