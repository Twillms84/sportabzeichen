<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use IServ\CoreBundle\Controller\AbstractPageController;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExam;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenParticipant;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenRequirement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sportabzeichen/manage')]
class ManageController extends AbstractPageController
{
    #[Route('/', name: 'sportabzeichen_manage_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // ----------------------------------------------------
        // STATISTIKEN ERMITTELN
        // ----------------------------------------------------

        $examCount = $em->getRepository(SportabzeichenExam::class)->count([]);
        $participantCount = $em->getRepository(SportabzeichenParticipant::class)->count([]);

        $years = $em->createQuery("
            SELECT DISTINCT r.jahr
            FROM PulsR\\SportabzeichenBundle\\Entity\\SportabzeichenRequirement r
        ")->getResult();
        $requirementYears = count($years);

        $stats = [
            'exams' => $examCount,
            'participants' => $participantCount,
            'requirementYears' => $requirementYears,
        ];

        return $this->render('@PulsRSportabzeichen/manage/index.html.twig', [
            'title' => _('Sportabzeichen Verwaltung'),
            'stats' => $stats,
        ]);
    }
}

