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
     * 1️⃣ Auswahlseite: Welche Prüfung soll bearbeitet werden?
     */
    #[Route('/', name: 'exams', methods: ['GET'])]
    public function examSelection(Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $exams = $conn->fetchAllAssociative("
            SELECT id, exam_name, exam_year, exam_date
            FROM sportabzeichen_exams
            ORDER BY exam_year DESC, exam_date DESC;
        ");

        return $this->render('@PulsRSportabzeichen/results/index.html.twig', [
            'exams' => $exams,
        ]);
    }

    /**
     * 2️⃣ Liste der Teilnehmer einer ausgewählten Prüfung
     */
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

        // Disziplinen holen
        $disciplines = $conn->fetchAllAssociative("
            SELECT id, name, kategorie, einheit
            FROM sportabzeichen_disciplines
            ORDER BY kategorie, name
        ");

        return $this->render('@PulsRSportabzeichen/results/exam_results.html.twig', [
            'exam'        => $exam,
            'participants'=> $participants,
            'disciplines' => $disciplines,
        ]);
    }

    /**
     * 3️⃣ Einzelansicht: Ergebnisse eines Teilnehmers
     */
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

    /**
     * 4️⃣ AJAX-Speichern
     */
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

        //----------------------------------------------
        // Teilnehmerdaten
        //----------------------------------------------
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

        $ak = sprintf("AC%02d%02d", $age, $age + 1);

        //----------------------------------------------
        // Anforderungen
        //----------------------------------------------
        $req = $conn->fetchAssociative("
            SELECT bronze, silber, gold, berechnungsart
            FROM sportabzeichen_requirements
            WHERE discipline_id = ?
              AND jahr = ?
              AND altersklasse = ?
              AND geschlecht = ?
        ", [$disciplineId, $jahr, $ak, $geschlecht]);

        $stufe = null;
        $points = null;

        if ($leistung !== null && $req) {
            switch ($req['berechnungsart']) {
                case 'GREATER':
                    if ($leistung >= $req['gold'])   { $stufe = 'Gold';   $points = 3; }
                    elseif ($leistung >= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                    elseif ($leistung >= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
                    break;

                case 'LOWER':
                    if ($leistung <= $req['gold'])   { $stufe = 'Gold';   $points = 3; }
                    elseif ($leistung <= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                    elseif ($leistung <= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
                    break;
            }
        }

        //----------------------------------------------
        // Speichern
        //----------------------------------------------
        $existing = $conn->fetchOne("
            SELECT id FROM sportabzeichen_exam_results
            WHERE ep_id = ? AND discipline_id = ?
        ", [$epId, $disciplineId]);

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
