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

    #[ORM\ManyToOne(targetEntity: SportabzeichenDiscipline::class)]
    #[ORM\JoinColumn(name: 'discipline_id', referencedColumnName: 'id')]
    private SportabzeichenDiscipline $discipline;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $leistung = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stufe = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $points = null;

    #[ORM\Column(type: 'datetimetz')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
