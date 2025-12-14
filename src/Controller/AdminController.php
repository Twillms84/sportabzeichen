<?php

declare(strict_types=1);

namespace IServ\Module\Sportabzeichen\Controller;

use Doctrine\DBAL\Connection;
use IServ\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Zentrale Verwaltungsoberfläche Sportabzeichen
 */
#[Route('/sportabzeichen/admin', name: 'sportabzeichen_admin_')]
#[IsGranted('sportabzeichen.admin')]
final class AdminController extends AbstractController
{
    /* ------------------------------------------------------------
     * Dashboard / Einstieg
     * ------------------------------------------------------------ */
    #[Route('/', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('sportabzeichen/admin/dashboard.html.twig', [
            'activeTab' => 'dashboard',
        ]);
    }

    /* ------------------------------------------------------------
     * Anforderungen
     * ------------------------------------------------------------ */
    #[Route('/requirements', name: 'requirements', methods: ['GET'])]
    #[IsGranted('sportabzeichen.admin')]
    public function requirements(Connection $conn): Response
    {
        $years = $conn->fetchFirstColumn(
            'SELECT DISTINCT jahr
             FROM sportabzeichen_requirements
             ORDER BY jahr DESC'
        );

        return $this->render('sportabzeichen/admin/requirements.html.twig', [
            'activeTab' => 'requirements',
            'years'     => $years,
        ]);
    }

    /* ------------------------------------------------------------
     * Teilnehmer
     * ------------------------------------------------------------ */
    #[Route('/participants', name: 'participants', methods: ['GET'])]
    #[IsGranted('sportabzeichen.admin')]
    public function participants(Connection $conn): Response
    {
        $participants = $conn->fetchAllAssociative(
            'SELECT id, vorname, nachname, geschlecht, geburtsdatum
             FROM sportabzeichen_participants
             ORDER BY nachname, vorname'
        );

        return $this->render('sportabzeichen/admin/participants.html.twig', [
            'activeTab'    => 'participants',
            'participants' => $participants,
        ]);
    }

    /* ------------------------------------------------------------
     * Prüfungen verwalten
     * ------------------------------------------------------------ */
    #[Route('/exams', name: 'exams', methods: ['GET'])]
    #[IsGranted('sportabzeichen.admin')]
    public function exams(Connection $conn): Response
    {
        $exams = $conn->fetchAllAssociative(
            'SELECT *
             FROM sportabzeichen_exams
             ORDER BY exam_year DESC, exam_date DESC'
        );

        return $this->render('sportabzeichen/admin/exams.html.twig', [
            'activeTab' => 'exams',
            'exams'     => $exams,
        ]);
    }
}
