<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Zentrale Verwaltungsoberfläche Sportabzeichen
 */
#[Route('/sportabzeichen/admin', name: 'sportabzeichen_admin_')]
final class AdminController extends AbstractPageController
{
    /* ------------------------------------------------------------
     * Dashboard / Einstieg
     * ------------------------------------------------------------ */
    #[Route('/', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_ADMIN');

        return $this->render('@PulsRSportabzeichen/admin/dashboard.html.twig', [
            'activeTab' => 'dashboard',
        ]);
    }

    /* ------------------------------------------------------------
     * Anforderungen
     * ------------------------------------------------------------ */
    #[Route('/requirements', name: 'requirements', methods: ['GET'])]
    public function requirements(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_REQUIREMENTS');

        $years = $conn->fetchFirstColumn("
            SELECT DISTINCT jahr
            FROM sportabzeichen_requirements
            ORDER BY jahr DESC
        ");

        return $this->render('@PulsRSportabzeichen/admin/requirements.html.twig', [
            'activeTab' => 'requirements',
            'years'     => $years,
        ]);
    }

    /* ------------------------------------------------------------
     * Teilnehmer
     * ------------------------------------------------------------ */
    #[Route('/participants', name: 'participants', methods: ['GET'])]
    public function participants(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE_PARTICIPANTS');

        $participants = $conn->fetchAllAssociative("
            SELECT id, vorname, nachname, geschlecht, geburtsdatum
            FROM sportabzeichen_participants
            ORDER BY nachname, vorname
        ");

        return $this->render('@PulsRSportabzeichen/admin/participants.html.twig', [
            'activeTab'   => 'participants',
            'participants'=> $participants,
        ]);
    }

    /* ------------------------------------------------------------
     * Prüfungen verwalten
     * ------------------------------------------------------------ */
    #[Route('/exams', name: 'exams', methods: ['GET'])]
    public function exams(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_ADMIN');

        $exams = $conn->fetchAllAssociative("
            SELECT *
            FROM sportabzeichen_exams
            ORDER BY exam_year DESC, exam_date DESC
        ");

        return $this->render('@PulsRSportabzeichen/admin/exams.html.twig', [
            'activeTab' => 'exams',
            'exams'     => $exams,
        ]);
    }
}
