<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ergebnisseingabe für Prüfungen
 */
#[Route('/sportabzeichen/exams/results', name: 'sportabzeichen_results_')]
final class ExamResultController extends AbstractPageController
{
    /**
     * 1️⃣ Prüfung auswählen
     */
    #[Route('/', name: 'exams', methods: ['GET'])]
    public function examSelection(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $exams = $conn->fetchAllAssociative("
            SELECT id, exam_name, exam_year, exam_date
            FROM sportabzeichen_exams
            ORDER BY exam_year DESC, exam_date DESC
        ");

        return $this->render('@PulsRSportabzeichen/results/index.html.twig', [
            'exams' => $exams
        ]);
    }


    /**
     * 2️⃣ Ergebnistabelle für eine Prüfung
     */
    #[Route('/exam/{examId}', name: 'index', methods: ['GET'])]
    public function index(int $examId, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $exam = $conn->fetchAssociative("
            SELECT id, exam_name, exam_year, exam_date
            FROM sportabzeichen_exams
            WHERE id = ?
        ", [$examId]);

        if (!$exam) {
            throw $this->createNotFoundException("Prüfung nicht gefunden.");
        }

        $participants = $conn->fetchAllAssociative("
            SELECT ep.id AS ep_id,
                   p.vorname, p.nachname,
                   p.geschlecht,
                   ep.age_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON p.id = ep.participant_id
            WHERE ep.exam_id = ?
            ORDER BY p.nachname, p.vorname
        ", [$examId]);

        // Disziplinen gruppieren nach Kategorie
        $disciplineRows = $conn->fetchAllAssociative("
            SELECT id, name, kategorie, einheit
            FROM sportabzeichen_disciplines
            ORDER BY kategorie, name
        ");

        $disciplines = [];
        foreach ($disciplineRows as $d) {
            $disciplines[$d['kategorie']][] = $d;
        }

        // Ergebnisse laden
        $resultsRows = $conn->fetchAllAssociative("
            SELECT * FROM sportabzeichen_exam_results
            WHERE ep_id IN (
                SELECT id FROM sportabzeichen_exam_participants WHERE exam_id = ?
            )
        ", [$examId]);

        $results = [];
        foreach ($resultsRows as $r) {
            $results[$r['ep_id']][$r['discipline_id']] = $r;
        }

        return $this->render('@PulsRSportabzeichen/results/exam_results.html.twig', [
            'exam'        => $exam,
            'participants'=> $participants,
            'disciplines' => $disciplines,
            'results'     => $results,
            'filters'     => [
                'gender' => '',
                'class'  => '',
                'search' => ''
            ]
        ]);
    }


    /**
     * 3️⃣ Einzelansicht: Ergebnisse eines Teilnehmers
     */
    #[Route('/{examId}/edit/{epId}', name: 'edit', methods: ['GET'])]
    public function edit(int $examId, int $epId, Connection $conn): Response
    {
        // (bleibt unverändert wie bei dir)
    }


    /**
     * 4️⃣ AJAX speichern
     */
    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request, Connection $conn): Response
    {
        // (bleibt unverändert wie bei dir)
    }

        /**
     * Alle fehlenden Teilnehmer automatisch zur Prüfung hinzufügen
     */
    #[Route('/{id}/participants/auto-add', name: 'auto_add_participants', methods: ['POST'])]
    public function autoAddParticipants(int $id, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        // Prüfung laden
        $exam = $conn->fetchAssociative("SELECT * FROM sportabzeichen_exams WHERE id = ?", [$id]);
        if (!$exam) {
            throw $this->createNotFoundException("Prüfung nicht gefunden");
        }

        // Alle Schüler laden, die überhaupt als Teilnehmer existieren können
        $all = $conn->fetchAllAssociative("
            SELECT id, geburtsdatum
            FROM sportabzeichen_participants
            WHERE geschlecht IS NOT NULL
            AND geburtsdatum IS NOT NULL
        ");

        // Bereits eingetragene Teilnehmer
        $existing = $conn->fetchFirstColumn("
            SELECT participant_id
            FROM sportabzeichen_exam_participants
            WHERE exam_id = ?
        ", [$id]);

        $added = 0;

        foreach ($all as $p) {

            if (in_array($p['id'], $existing)) {
                continue; // schon drin → überspringen
            }

            // Alter berechnen
            $birthYear = (int)date('Y', strtotime($p['geburtsdatum']));
            $ageYear = (int)$exam['exam_year'] - $birthYear;

            // Einfügen
            $conn->insert('sportabzeichen_exam_participants', [
                'exam_id'       => $id,
                'participant_id'=> $p['id'],
                'age_year'      => $ageYear,
            ]);

            $added++;
        }

        $this->addFlash('success', "$added Teilnehmer automatisch hinzugefügt.");

        return $this->redirectToRoute('sportabzeichen_exam_participants', ['id' => $id]);
        }
}
