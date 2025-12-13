<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Verwaltung von Prüfungen und Teilnehmerzuordnung
 */
#[Route('/sportabzeichen/exams', name: 'sportabzeichen_exam_')]
final class ExamController extends AbstractPageController
{
    /* ------------------------------------------------------------
     * Prüfungen verwalten
     * ------------------------------------------------------------ */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_ADMIN');

        $exams = $conn->fetchAllAssociative("
            SELECT *
            FROM sportabzeichen_exams
            ORDER BY exam_year DESC, exam_date DESC
        ");

        return $this->render('@PulsRSportabzeichen/exams/index.html.twig', [
            'exams' => $exams,
        ]);
    }

    /* ------------------------------------------------------------
     * Teilnehmer einer Prüfung
     * ------------------------------------------------------------ */
    #[Route('/{id}/participants', name: 'participants', methods: ['GET'])]
    public function participants(int $id, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_ADMIN');

        $exam = $conn->fetchAssociative(
            "SELECT * FROM sportabzeichen_exams WHERE id = ?",
            [$id]
        );

        if (!$exam) {
            throw $this->createNotFoundException();
        }

        $participants = $conn->fetchAllAssociative("
            SELECT
                ep.id AS ep_id,
                p.vorname,
                p.nachname,
                p.geschlecht,
                ep.age_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON p.id = ep.participant_id
            WHERE ep.exam_id = ?
            ORDER BY p.nachname, p.vorname
        ", [$id]);

        return $this->render('@PulsRSportabzeichen/exams/participants.html.twig', [
            'exam'        => $exam,
            'participants'=> $participants,
        ]);
    }

    /* ------------------------------------------------------------
     * Teilnehmer automatisch hinzufügen
     * ------------------------------------------------------------ */
    #[Route('/{id}/participants/auto-add', name: 'auto_add', methods: ['POST'])]
    public function autoAddParticipants(int $id, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_ADMIN');

        $exam = $conn->fetchAssociative(
            "SELECT * FROM sportabzeichen_exams WHERE id = ?",
            [$id]
        );

        if (!$exam) {
            throw $this->createNotFoundException();
        }

        $allParticipants = $conn->fetchAllAssociative("
            SELECT id, geburtsdatum
            FROM sportabzeichen_participants
            WHERE geburtsdatum IS NOT NULL
        ");

        $existing = $conn->fetchFirstColumn("
            SELECT participant_id
            FROM sportabzeichen_exam_participants
            WHERE exam_id = ?
        ", [$id]);

        foreach ($allParticipants as $p) {
            if (in_array($p['id'], $existing, true)) {
                continue;
            }

            $ageYear = $exam['exam_year'] - (int)substr($p['geburtsdatum'], 0, 4);

            $conn->insert('sportabzeichen_exam_participants', [
                'exam_id'        => $id,
                'participant_id' => $p['id'],
                'age_year'       => $ageYear,
            ]);
        }

        return $this->redirectToRoute('sportabzeichen_exam_participants', [
            'id' => $id,
        ]);
    }
}
