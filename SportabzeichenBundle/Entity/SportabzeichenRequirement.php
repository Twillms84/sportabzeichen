<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="sportabzeichen_requirements")
 */
class SportabzeichenRequirement implements CrudInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="integer") */
    private int $jahr;

    /** @ORM\Column(type="text") */
    private string $altersklasse;

    /** @ORM\Column(type="text") */
    private string $geschlecht;

    /** @ORM\Column(type="integer") */
    private int $auswahlnummer;

    /** @ORM\Column(type="text") */
    private string $disziplin;

    /** @ORM\Column(type="text") */
    private string $kategorie;

    /** @ORM\Column(type="float", nullable=true) */
    private ?float $bronze = null;

    /** @ORM\Column(type="float", nullable=true) */
    private ?float $silber = null;

    /** @ORM\Column(type="float", nullable=true) */
    private ?float $gold = null;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $einheit = null;

    /** @ORM\Column(type="boolean", options={"default": false}) */
    private bool $schwimmnachweis = false;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $berechnungsart = 'GREATER';

    public function __toString(): string
    {
        return sprintf('%s (%s, %s)', $this->disziplin, $this->altersklasse, $this->geschlecht);
    }

    // --- GETTER / SETTER ---

    public function getId(): int { return $this->id; }

    public function getJahr(): int { return $this->jahr; }
    public function setJahr(int $jahr): self { $this->jahr = $jahr; return $this; }

    public function getAltersklasse(): string { return $this->altersklasse; }
    public function setAltersklasse(string $ak): self { $this->altersklasse = $ak; return $this; }

    public function getGeschlecht(): string { return $this->geschlecht; }
    public function setGeschlecht(string $g): self { $this->geschlecht = $g; return $this; }

    public function getAuswahlnummer(): int { return $this->auswahlnummer; }
    public function setAuswahlnummer(int $nr): self { $this->auswahlnummer = $nr; return $this; }

    public function getDisziplin(): string { return $this->disziplin; }
    public function setDisziplin(string $d): self { $this->disziplin = $d; return $this; }

    public function getKategorie(): string { return $this->kategorie; }
    public function setKategorie(string $k): self { $this->kategorie = $k; return $this; }

    public function getBronze(): ?float { return $this->bronze; }
    public function setBronze(?float $v): self { $this->bronze = $v; return $this; }

    public function getSilber(): ?float { return $this->silber; }
    public function setSilber(?float $v): self { $this->silber = $v; return $this; }

    public function getGold(): ?float { return $this->gold; }
    public function setGold(?float $v): self { $this->gold = $v; return $this; }

    public function getEinheit(): ?string { return $this->einheit; }
    public function setEinheit(?string $e): self { $this->einheit = $e; return $this; }

    public function hasSchwimmnachweis(): bool { return $this->schwimmnachweis; }
    public function setSchwimmnachweis(bool $v): self { $this->schwimmnachweis = $v; return $this; }

    public function getBerechnungsart(): ?string { return $this->berechnungsart; }
    public function setBerechnungsart(?string $b): self { $this->berechnungsart = $b; return $this; }
}
