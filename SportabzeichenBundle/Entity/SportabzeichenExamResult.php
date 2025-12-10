<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sportabzeichen_exam_results')]
class SportabzeichenExamResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: SportabzeichenExamParticipant::class, inversedBy: 'results')]
    #[ORM\JoinColumn(name: 'ep_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private SportabzeichenExamParticipant $examParticipant;

    #[ORM\Column(type: 'text')]
    private string $disziplin;

    #[ORM\Column(type: 'text')]
    private string $kategorie;

    #[ORM\Column(type: 'integer')]
    private int $auswahlnummer;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $leistung = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stufe = null;

    // GETTER & SETTER …
}
