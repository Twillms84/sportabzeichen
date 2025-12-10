<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'sportabzeichen_exam_participants')]
class SportabzeichenExamParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: SportabzeichenExam::class, inversedBy: 'examParticipants')]
    #[ORM\JoinColumn(name: 'exam_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private SportabzeichenExam $exam;

    #[ORM\ManyToOne(targetEntity: SportabzeichenParticipant::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private SportabzeichenParticipant $participant;

    #[ORM\Column(type: 'integer')]
    private int $ageYear;

    #[ORM\OneToMany(mappedBy: 'examParticipant', targetEntity: SportabzeichenExamResult::class, cascade: ['remove'])]
    private Collection $results;

    public function __construct()
    {
        $this->results = new ArrayCollection();
    }

    // GETTER & SETTER …
}
