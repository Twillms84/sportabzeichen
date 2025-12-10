<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

#[ORM\Entity]
#[ORM\Table(name: 'sportabzeichen_requirements')]
class SportabzeichenRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: SportabzeichenDiscipline::class)]
    #[ORM\JoinColumn(name: 'discipline_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private SportabzeichenDiscipline $discipline;

    #[ORM\Column(type: 'integer')]
    private int $jahr;

    #[ORM\Column(type: 'text')]
    private string $altersklasse;

    #[ORM\Column(type: 'text')]
    private string $geschlecht;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $bronze = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $silber = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $gold = null;

    #[ORM\Column(type: 'boolean')]
    private bool $schwimmnachweis = false;

    public function __toString(): string
    {
        return sprintf('%s (%s %s %d)', 
            $this->discipline->getName(),
            $this->geschlecht,
            $this->altersklasse,
            $this->jahr
        );
    }
}
