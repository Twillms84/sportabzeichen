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

    /**
     * Eindeutige IServ/LDAP-ID
     * Wird per CSV-Import gesetzt.
     */
    #[ORM\Column(type: 'text', unique: true)]
    private string $importId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $vorname = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nachname = null;

    /**
     * 'm', 'w', 'd'
     * Ebenfalls per CSV gesetzt.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $geschlecht = null;

    /**
     * Kommt ebenfalls aus dem CSV-Upload.
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeImmutable $geburtsdatum = null;

    #[ORM\Column(type: 'datetimetz')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- GETTER & SETTER ---

    public function getId(): int 
    { 
        return $this->id; 
    }

    public function getImportId(): string 
    { 
        return $this->importId; 
    }

    public function setImportId(string $id): self
    {
        $this->importId = $id;
        return $this;
    }

    public function getVorname(): ?string 
    {
        return $this->vorname;
    }

    public function setVorname(?string $v): self
    {
        $this->vorname = $v;
        return $this;
    }

    public function getNachname(): ?string 
    {
        return $this->nachname;
    }

    public function setNachname(?string $n): self
    {
        $this->nachname = $n;
        return $this;
    }

    public function getGeschlecht(): ?string
    {
        return $this->geschlecht;
    }

    public function setGeschlecht(?string $g): self
    {
        $this->geschlecht = $g;
        return $this;
    }

    public function getGeburtsdatum(): ?\DateTimeImmutable 
    {
        return $this->geburtsdatum;
    }

    public function setGeburtsdatum(?\DateTimeImmutable $d): self
    {
        $this->geburtsdatum = $d;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable 
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $dt): self
    {
        $this->updatedAt = $dt;
        return $this;
    }

    public function __toString(): string
    {
        return trim(($this->nachname ?? '') . ' ' . ($this->vorname ?? ''));
    }
}
