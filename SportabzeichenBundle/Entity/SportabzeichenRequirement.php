<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IServ\CrudBundle\Entity\CrudInterface;

/**
 * Anforderungen des Deutschen Sportabzeichens
 * für ein bestimmtes Jahr, eine Altersklasse,
 * ein Geschlecht, eine Kategorie und eine Disziplin.
 */
#[ORM\Entity]
#[ORM\Table(name: "sportabzeichen_requirements")]
class SportabzeichenRequirement implements CrudInterface
{
    public const GESCHLECHT_MALE   = 'MALE';
    public const GESCHLECHT_FEMALE = 'FEMALE';
    public const GESCHLECHT_DIVERS = 'DIVERS';

    public const BERECHNUNG_GREATER = 'GREATER'; // Leistung > Grenzwert
    public const BERECHNUNG_LOWER   = 'LOWER';   // Leistung < Grenzwert
    public const BERECHNUNG_EQUAL   = 'EQUAL';   // Leistung == Grenzwert

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    /** Jahr des Kataloges (z.B. 2024) */
    #[ORM\Column(type: "integer")]
    private int $jahr;

    /**
     * Altersklasse-Codes AC0607, AC0809, AC1011 ...
     * Wird NICHT berechnet – kommt aus der DOSB-Katalog-Definition.
     */
    #[ORM\Column(type: "string")]
    private string $altersklasse;

    /** MALE, FEMALE, DIVERS */
    #[ORM\Column(type: "string")]
    private string $geschlecht;

    /**
     * Auswahlnummer:
     * Wird zur Sortierung im Dropdown verwendet!!
     *
     * Beispiel:
     * - Ausdauer  → 4
     * - Kraft     → 3
     * - Schnelligkeit → 2
     * - Koordination → 1
     */
    #[ORM\Column(type: "integer")]
    private int $auswahlnummer;

    /** konkrete Disziplin (z. B. „Weitsprung“, „100m Sprint“) */
    #[ORM\Column(type: "string")]
    private string $disziplin;

    /**
     * Kategorie der Disziplin:
     * z. B. "Laufen", "Springen", "Wurf", "Schwimmen"
     */
    #[ORM\Column(type: "string")]
    private string $kategorie;

    /** Grenzwert für Bronze */
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $bronze = null;

    /** Grenzwert für Silber */
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $silber = null;

    /** Grenzwert für Gold */
    #[ORM\Column(type: "float", nullable: true)]
    private ?float $gold = null;

    /** Einheit: "m", "s", "cm", "Punkte", etc. */
    #[ORM\Column(type: "string", nullable: true)]
    private ?string $einheit = null;

    /** Schwimmnachweis für diese Disziplin erforderlich? */
    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $schwimmnachweis = false;

    /** GREATER, LOWER oder EQUAL */
    #[ORM\Column(type: "string", nullable: true)]
    private ?string $berechnungsart = self::BERECHNUNG_GREATER;

    public function __toString(): string
    {
        return sprintf(
            '%s (%s, %s, %d)',
            $this->disziplin,
            $this->altersklasse,
            $this->geschlecht,
            $this->jahr
        );
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

