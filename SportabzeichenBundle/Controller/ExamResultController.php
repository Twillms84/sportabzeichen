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
    /* --------------------------------------------------------
     * Altersklassen-Mapping nach DSA-Regelwerk
     * -------------------------------------------------------- */
    private function mapAgeToAltersklasse(int $age): string
    {
        $mapping = [
            [6, 7,   "AC0607"],
            [8, 9,   "AC0809"],
            [10, 11, "AC1011"],
            [12, 13, "AC1213"],
            [14, 15, "AC1415"],
            [16, 17, "AC1617"],
            [18, 19, "AC1819"],
            [20, 24, "AC2024"],
            [25, 29, "AC2529"],
            [30, 34, "AC3034"],
            [35, 39, "AC3539"],
            [40, 44, "AC4044"],
            [45, 49, "AC4549"],
            [50, 54, "AC5054"],
            [55, 59, "AC5559"],
            [60, 64, "AC6064"],
            [65, 69, "AC6569"],
            [70, 74, "AC7074"],
            [75, 79, "AC7579"],
            [80, 84, "AC8084"],
            [85, 89, "AC8589"],
            [90, 200, "AC9000"],
        ];

        foreach ($mapping as [$min, $max, $label]) {
            if ($age >= $min && $age <= $max) {
                return $label;
            }
        }
        return "AC2024";
    }


    /* --------------------------------------------------------
     * 1️⃣ Auswahlseite – Prüfung wählen
     * -------------------------------------------------------- */
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
            'exams' => $exams,
        ]);
    }


    /* --------------------------------------------------------
     * 2️⃣ Ergebnisse eingeben – Liste Teilnehmer + Disziplinen
     * -------------------------------------------------------- */
    #[Route('/exam/{examId}', name: 'index', methods: ['GET'])]
    public function index(int $examId, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        // Prüfung laden
        $exam = $conn->fetchAssociative("
            SELECT id, exam_name, exam_year, exam_date
            FROM sportabzeichen_exams
            WHERE id = ?
        ", [$examId]);

        if (!$exam) {
            throw $this->createNotFoundException("Prüfung nicht gefunden.");
        }

        // Teilnehmer laden
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


        /* --------------------------------------------------------
         * Disziplinen + Anforderungen gemeinsam laden
         * -------------------------------------------------------- */
        $disciplineRows = $conn->fetchAllAssociative("
            SELECT 
                d.id,
                d.name,
                d.kategorie,
                d.einheit,
                r.altersklasse,
                r.geschlecht,
                r.auswahlnummer
            FROM sportabzeichen_disciplines d
            JOIN sportabzeichen_requirements r
              ON d.id = r.discipline_id
            WHERE r.jahr = ?
              AND LOWER(d.kategorie) <> 'schwimmen'
            ORDER BY d.kategorie, r.auswahlnummer, d.name
        ", [$exam['exam_year']]);

        // Gruppieren nach Kategorie
        $disciplines = [];
        foreach ($disciplineRows as $row) {
            $disciplines[$row['kategorie']][] = $row;
        }
        foreach ($disciplines as &$items) {
            usort($items, fn($a, $b) => ($a['auswahlnummer'] <=> $b['auswahlnummer']));
        }
        unset($items);

        // Ergebnisse laden
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
            'exam'        => $exam,
            'participants'=> $participants,
            'disciplines' => $disciplines,
            'results'     => $results,
        ]);
    }


    /* --------------------------------------------------------
     * 3️⃣ Einzelansicht (optional)
     * -------------------------------------------------------- */
    #[Route('/{examId}/edit/{epId}', name: 'edit', methods: ['GET'])]
    public function edit(int $examId, int $epId, Connection $conn): Response
    {
        // unverändert – kann bleiben
        return new Response("Not implemented for now");
    }


    /* --------------------------------------------------------
     * 4️⃣ AJAX-Speichern
     * -------------------------------------------------------- */
    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $epId         = (int)$request->request->get('ep_id');
        $disciplineId = (int)$request->request->get('discipline_id');
        $leistungRaw  = $request->request->get('leistung');

        $leistung = ($leistungRaw === '' || $leistungRaw === null)
            ? null
            : (float)$leistungRaw;

        // Teilnehmer laden
        $ep = $conn->fetchAssociative("
            SELECT ep.age_year, p.geschlecht AS sex, e.exam_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON p.id = ep.participant_id
            JOIN sportabzeichen_exams e ON ep.exam_id = e.id
            WHERE ep.id = ?
        ", [$epId]);

        if (!$ep) {
            return new Response("NOT FOUND", 404);
        }

        // Geschlecht mappen
        $genderMap = ['m' => 'MALE', 'w' => 'FEMALE'];
        $geschlecht = $genderMap[strtolower($ep['sex'])] ?? null;

        // Altersklasse berechnen
        $ak = $this->mapAgeToAltersklasse((int)$ep['age_year']);

        // Anforderungen laden
        $req = $conn->fetchAssociative("
            SELECT bronze, silber, gold, berechnungsart
            FROM sportabzeichen_requirements
            WHERE discipline_id = ?
              AND jahr = ?
              AND altersklasse = ?
              AND geschlecht = ?
        ", [$disciplineId, $ep['exam_year'], $ak, $geschlecht]);

        $stufe = null;
        $points = null;

        if ($leistung !== null && $req) {
            if ($req['berechnungsart'] === 'GREATER') {
                if ($leistung >= $req['gold'])   { $stufe = 'Gold'; $points = 3; }
                elseif ($leistung >= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                elseif ($leistung >= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
            } else {
                if ($leistung <= $req['gold'])   { $stufe = 'Gold'; $points = 3; }
                elseif ($leistung <= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                elseif ($leistung <= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
            }
        }

        // Existiert ein Eintrag?
        $existing = $conn->fetchOne("
            SELECT id FROM sportabzeichen_exam_results
            WHERE ep_id = ? AND discipline_id = ?
        ", [$epId, $disciplineId]);

        // Speichern
        if ($existing) {
            $conn->update('sportabzeichen_exam_results', [
                'leistung' => $leistung,
                'stufe'    => $stufe,
                'points'   => $points,
            ], ['id' => $existing]);
        } else {
            $conn->insert('sportabzeichen_exam_results', [
                'ep_id'         => $epId,
                'discipline_id' => $disciplineId,
                'leistung'      => $leistung,
                'stufe'         => $stufe,
                'points'        => $points,
            ]);
        }

        return new Response('OK');
    }
}
