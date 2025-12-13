<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ergebnisseingabe für Sportabzeichen
 */
#[Route('/sportabzeichen/results', name: 'sportabzeichen_results_')]
final class ExamResultController extends AbstractPageController
{
    /* ------------------------------------------------------------
     * Altersklasse bestimmen (zentral, kein Twig-Kram mehr)
     * ------------------------------------------------------------ */
    private function mapAgeToAltersklasse(int $age): string
    {
        $mapping = [
            [6, 7, "AC0607"], [8, 9, "AC0809"], [10, 11, "AC1011"],
            [12, 13, "AC1213"], [14, 15, "AC1415"], [16, 17, "AC1617"],
            [18, 19, "AC1819"], [20, 24, "AC2024"], [25, 29, "AC2529"],
            [30, 34, "AC3034"], [35, 39, "AC3539"], [40, 44, "AC4044"],
            [45, 49, "AC4549"], [50, 54, "AC5054"], [55, 59, "AC5559"],
            [60, 64, "AC6064"], [65, 69, "AC6569"], [70, 74, "AC7074"],
            [75, 79, "AC7579"], [80, 84, "AC8084"], [85, 89, "AC8589"],
            [90, 200, "AC9000"],
        ];

        foreach ($mapping as [$min, $max, $label]) {
            if ($age >= $min && $age <= $max) {
                return $label;
            }
        }
        return 'AC2024';
    }

    /* ------------------------------------------------------------
     * 1️⃣ Prüfungen auswählen
     * ------------------------------------------------------------ */
    #[Route('/', name: 'exams', methods: ['GET'])]
    public function exams(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_RESULTS');

        $exams = $conn->fetchAllAssociative("
            SELECT id, exam_name, exam_year, exam_date
            FROM sportabzeichen_exams
            ORDER BY exam_year DESC, exam_date DESC
        ");

        return $this->render('@PulsRSportabzeichen/results/exams.html.twig', [
            'exams' => $exams,
        ]);
    }

    /* ------------------------------------------------------------
     * 2️⃣ Ergebnisse einer Prüfung
     * ------------------------------------------------------------ */
    #[Route('/exam/{examId}', name: 'exam', methods: ['GET'])]
    public function exam(int $examId, Request $request, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_RESULTS');

        // Prüfung
        $exam = $conn->fetchAssociative("
            SELECT *
            FROM sportabzeichen_exams
            WHERE id = ?
        ", [$examId]);

        if (!$exam) {
            throw $this->createNotFoundException('Prüfung nicht gefunden');
        }

        // Teilnehmer
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
        ", [$examId]);

        // Altersklasse + Gender vorberechnen (kein Twig-Gehacke mehr)
        foreach ($participants as &$p) {
            $p['altersklasse'] = $this->mapAgeToAltersklasse((int)$p['age_year']);
            $p['gender'] = strtolower(trim($p['geschlecht'])) === 'm' ? 'MALE' : 'FEMALE';
        }
        unset($p);

        // Disziplinen + Anforderungen
        $disciplineRows = $conn->fetchAllAssociative("
            SELECT
                d.id,
                d.name,
                d.kategorie,
                r.altersklasse,
                r.geschlecht,
                r.auswahlnummer
            FROM sportabzeichen_disciplines d
            JOIN sportabzeichen_requirements r ON r.discipline_id = d.id
            WHERE r.jahr = ?
            ORDER BY d.kategorie, r.auswahlnummer, d.name
        ", [$exam['exam_year']]);

        $disciplines = [];
        foreach ($disciplineRows as $row) {
            $disciplines[$row['kategorie']][] = $row;
        }

        // Ergebnisse
        $resultsRaw = $conn->fetchAllAssociative("
            SELECT *
            FROM sportabzeichen_exam_results
            WHERE ep_id IN (
                SELECT id FROM sportabzeichen_exam_participants WHERE exam_id = ?
            )
        ", [$examId]);

        $results = [];
        foreach ($resultsRaw as $r) {
            $results[$r['ep_id']][$r['discipline_id']] = $r;
        }

        return $this->render('@PulsRSportabzeichen/results/exam_results.html.twig', [
            'exam'         => $exam,
            'participants' => $participants,
            'disciplines'  => $disciplines,
            'results'      => $results,
        ]);
    }

    /* ------------------------------------------------------------
     * 3️⃣ Massenspeichern (POST)
     * ------------------------------------------------------------ */
    #[Route('/save-many', name: 'save_many', methods: ['POST'])]
    public function saveMany(Request $request, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_RESULTS');

        $entries = json_decode($request->getContent(), true);
        if (!is_array($entries)) {
            return $this->json(['error' => 'Invalid payload'], 400);
        }

        foreach ($entries as $e) {
            $conn->executeStatement("
                INSERT INTO sportabzeichen_exam_results (ep_id, discipline_id, leistung)
                VALUES (:ep, :disc, :leistung)
                ON CONFLICT (ep_id, discipline_id)
                DO UPDATE SET leistung = EXCLUDED.leistung
            ", [
                'ep'       => (int)$e['ep_id'],
                'disc'     => (int)$e['discipline_id'],
                'leistung' => $e['leistung'],
            ]);
        }

        return $this->json(['ok' => true]);
    }
}
