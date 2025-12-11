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
     * Altersklasse-Mapping nach DSA-Regelwerk
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
            [20, 25, "AC2025"],
            [26, 30, "AC2630"],
            [31, 35, "AC3135"],
            [36, 40, "AC3640"],
            [41, 45, "AC4145"],
            [46, 50, "AC4650"],
            [51, 55, "AC5155"],
            [56, 60, "AC5660"],
            [61, 65, "AC6165"],
            [66, 70, "AC6670"],
            [71, 75, "AC7175"],
            [76, 80, "AC7680"],
            [81, 85, "AC8185"],
            [86, 90, "AC8690"],
            [91, 95, "AC9195"],
            [96, 100, "AC96100"],
        ];

        foreach ($mapping as [$min, $max, $label]) {
            if ($age >= $min && $age <= $max) {
                return $label;
            }
        }

        return "AC2025"; // fallback
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

        return $this->render('@PulsRSportabzeichen/results/exams.html.twig', [
            'exams' => $exams,
        ]);
    }


    /* --------------------------------------------------------
     * 2️⃣ Ergebnisse eingeben – Liste der Teilnehmer + Disziplinen
     * -------------------------------------------------------- */
    #[Route('/exam/{examId}', name: 'index', methods: ['GET'])]
    public function index(int $examId, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        // Prüfung holen
        $exam = $conn->fetchAssociative("
            SELECT id, exam_name, exam_year, exam_date
            FROM sportabzeichen_exams
            WHERE id = ?
        ", [$examId]);

        if (!$exam) {
            throw $this->createNotFoundException("Prüfung nicht gefunden.");
        }

        // Teilnehmer holen
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

        // Disziplinen holen (Schwimmen rausfiltern)
        $disciplineRows = $conn->fetchAllAssociative("
            SELECT id, name, kategorie, einheit
            FROM sportabzeichen_disciplines
            WHERE LOWER(kategorie) <> 'schwimmen'
            ORDER BY kategorie, name
        ");

        // Gruppierung: "Ausdauer" → [Disziplin 1, Disziplin 2]
        $disciplines = [];
        foreach ($disciplineRows as $row) {
            $disciplines[$row['kategorie']][] = $row;
        }

        // Bereits vorhandene Ergebnisse holen
        $resultsRaw = $conn->fetchAllAssociative("
            SELECT *
            FROM sportabzeichen_exam_results
            WHERE ep_id IN (
                SELECT id FROM sportabzeichen_exam_participants WHERE exam_id = ?
            )
        ", [$examId]);

        // Ergebnisse in Map überführen
        $results = [];
        foreach ($resultsRaw as $r) {
            $results[$r['ep_id']][$r['discipline_id']] = $r;
        }

        return $this->render('@PulsRSportabzeichen/results/exam_results.html.twig', [
            'exam'        => $exam,
            'participants'=> $participants,
            'disciplines' => $disciplines,
            'results'     => $results,
            'filters'     => [
                'gender' => $_GET['gender'] ?? '',
                'class'  => $_GET['class'] ?? '',
                'search' => $_GET['search'] ?? '',
            ]
        ]);
    }


    /* --------------------------------------------------------
     * 3️⃣ Einzelansicht (falls weiterhin benötigt)
     * -------------------------------------------------------- */
    #[Route('/{examId}/edit/{epId}', name: 'edit', methods: ['GET'])]
    public function edit(int $examId, int $epId, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $ep = $conn->fetchAssociative("
            SELECT ep.id AS ep_id,
                   ep.age_year,
                   p.vorname, p.nachname, p.geschlecht,
                   e.exam_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON p.id = ep.participant_id
            JOIN sportabzeichen_exams e ON ep.exam_id = e.id
            WHERE ep.id = ?
        ", [$epId]);

        if (!$ep) {
            throw $this->createNotFoundException("Teilnehmer nicht gefunden.");
        }

        $disciplines = $conn->fetchAllAssociative("
            SELECT id, name, kategorie, einheit, berechnungsart
            FROM sportabzeichen_disciplines
            WHERE LOWER(kategorie) <> 'schwimmen'
            ORDER BY kategorie, name
        ");

        $resultsRaw = $conn->fetchAllAssociative("
            SELECT *
            FROM sportabzeichen_exam_results
            WHERE ep_id = ?
        ", [$epId]);

        $results = [];
        foreach ($resultsRaw as $r) {
            $results[$r['discipline_id']] = $r;
        }

        return $this->render('@PulsRSportabzeichen/results/edit.html.twig', [
            'examId'      => $examId,
            'epId'        => $epId,
            'participant' => $ep,
            'disciplines' => $disciplines,
            'results'     => $results,
        ]);
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

        // Teilnehmerdaten laden
        $ep = $conn->fetchAssociative("
            SELECT ep.age_year, p.geschlecht, e.exam_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON p.id = ep.participant_id
            JOIN sportabzeichen_exams e ON ep.exam_id = e.id
            WHERE ep.id = ?
        ", [$epId]);

        if (!$ep) {
            return new Response("NOT FOUND", 404);
        }

        $age        = (int)$ep['age_year'];
        $geschlecht = $ep['geschlecht'];
        $jahr       = (int)$ep['exam_year'];

        // Altersklasse korrekt berechnen
        $ak = $this->mapAgeToAltersklasse($age);

        // Anforderungen holen
        $req = $conn->fetchAssociative("
            SELECT bronze, silber, gold, berechnungsart
            FROM sportabzeichen_requirements
            WHERE discipline_id = ?
              AND jahr = ?
              AND altersklasse = ?
              AND geschlecht = ?
        ", [$disciplineId, $jahr, $ak, $geschlecht]);

        // Bewertung
        $stufe = null;
        $points = null;

        if ($leistung !== null && $req) {
            if ($req['berechnungsart'] === 'GREATER') {
                if ($leistung >= $req['gold'])   { $stufe = 'Gold'; $points = 3; }
                elseif ($leistung >= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                elseif ($leistung >= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
            } elseif ($req['berechnungsart'] === 'LOWER') {
                if ($leistung <= $req['gold'])   { $stufe = 'Gold'; $points = 3; }
                elseif ($leistung <= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                elseif ($leistung <= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
            }
        }

        // Vorheriges Ergebnis holen
        $existing = $conn->fetchOne("
            SELECT id FROM sportabzeichen_exam_results
            WHERE ep_id = ? AND discipline_id = ?
        ", [$epId, $disciplineId]);

        // Update oder Insert
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

