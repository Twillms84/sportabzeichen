<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'sportabzeichen_exams')]
class SportabzeichenExam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeImmutable $examDate = null;

    #[ORM\Column(type: 'integer')]
    private int $examYear;

    #[ORM\Column(type: 'datetimetz')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetimetz')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'exam', targetEntity: SportabzeichenExamParticipant::class)]
    private Collection $examParticipants;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->examParticipants = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // GETTER / SETTER …

    public function getId(): int { return $this->id; }

    public function getExamDate(): ?\DateTimeImmutable { return $this->examDate; }
    public function setExamDate(?\DateTimeImmutable $date): self { $this->examDate = $date; return $this; }

    public function getExamYear(): int { return $this->examYear; }
    public function setExamYear(int $year): self { $this->examYear = $year; return $this; }

    public function getExamParticipants(): Collection
    {
        return $this->examParticipants;
    }
}
