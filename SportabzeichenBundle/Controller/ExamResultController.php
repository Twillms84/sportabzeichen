<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ergebnisseingabe für Teilnehmer einer Prüfung
 */
#[Route(path: '/sportabzeichen/exams/results', name: 'sportabzeichen_results_')]
class ExamResultController extends AbstractPageController
{
    /**
     * Ergebnisse einer Person innerhalb einer Prüfung anzeigen/bearbeiten
     */
    #[Route(path: '/{examId}/edit/{epId}', name: 'edit', methods: ['GET'])]
    public function edit(
        int $examId,
        int $epId,
        Connection $conn
    ): Response {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        // Teilnehmer + Metadaten laden
        $ep = $conn->fetchAssociative("
            SELECT ep.id AS ep_id, ep.age_year, 
                   p.vorname, p.nachname, p.geschlecht, 
                   e.exam_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON ep.participant_id = p.id
            JOIN sportabzeichen_exams e ON ep.exam_id = e.id
            WHERE ep.id = ?
        ", [$epId]);

        if (!$ep) {
            throw $this->createNotFoundException("Teilnehmer nicht gefunden");
        }

        // Stammdaten: Disziplinen
        $disciplines = $conn->fetchAllAssociative("
            SELECT id, name, kategorie, einheit, berechnungsart
            FROM sportabzeichen_disciplines
            ORDER BY kategorie, name
        ");

        // Bereits vorhandene Ergebnisse
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
     * Ergebnisse speichern (AJAX POST)
     */
    #[Route(path: '/save', name: 'save', methods: ['POST'])]
    public function save(Request $request, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $epId         = (int)$request->request->get('ep_id');
        $disciplineId = (int)$request->request->get('discipline_id');
        $leistungRaw  = $request->request->get('leistung');

        // Leere Eingabe → NULL speichern
        $leistung = ($leistungRaw === '' || $leistungRaw === null)
            ? null
            : (float)$leistungRaw;

        //---------------------------------------------------
        // Teilnehmerdaten laden (für Bewertung)
        //---------------------------------------------------
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

        // Altersklasse bestimmen (AC0607, AC0809, ...)
        $ak = sprintf("AC%02d%02d", $age, $age + 1);

        //---------------------------------------------------
        // Anforderungen für diese Disziplin
        //---------------------------------------------------
        $req = $conn->fetchAssociative("
            SELECT bronze, silber, gold, berechnungsart
            FROM sportabzeichen_requirements
            WHERE discipline_id = ?
              AND jahr = ?
              AND altersklasse = ?
              AND geschlecht = ?
        ", [
            $disciplineId,
            $jahr,
            $ak,
            $geschlecht
        ]);

        $stufe  = null;
        $points = null;

        //---------------------------------------------------
        // Bronze/Silber/Gold Bewertung
        //---------------------------------------------------
        if ($leistung !== null && $req) {

            switch ($req['berechnungsart']) {

                case 'GREATER': // höhere Leistung = besser
                    if ($leistung >= $req['gold'])   { $stufe = 'Gold';   $points = 3; }
                    elseif ($leistung >= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                    elseif ($leistung >= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
                    break;

                case 'LOWER': // niedrigere Leistung = besser
                    if ($leistung <= $req['gold'])   { $stufe = 'Gold';   $points = 3; }
                    elseif ($leistung <= $req['silber']) { $stufe = 'Silber'; $points = 2; }
                    elseif ($leistung <= $req['bronze']) { $stufe = 'Bronze'; $points = 1; }
                    break;

                default:
                    $stufe = null;
                    $points = null;
            }
        }

        //---------------------------------------------------
        // Existiert ein Eintrag?
        //---------------------------------------------------
        $existing = $conn->fetchOne("
            SELECT id FROM sportabzeichen_exam_results
            WHERE ep_id = ? AND discipline_id = ?
        ", [$epId, $disciplineId]);

        //---------------------------------------------------
        // UPDATE
        //---------------------------------------------------
        if ($existing) {

            $conn->update('sportabzeichen_exam_results', [
                'leistung' => $leistung,
                'stufe'    => $stufe,
                'points'   => $points,
            ], [
                'id' => $existing
            ]);
        }

        //---------------------------------------------------
        // INSERT
        //---------------------------------------------------
        else {

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
