<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ExamController extends AbstractController
{
    #[Route('/sportabzeichen/exams', name: 'sportabzeichen_exams')]
    public function index(Request $request, Connection $db): Response
    {
        $jahr = (int) date('Y');

        // -----------------------------
        // Filter
        // -----------------------------
        $klasse = $request->query->get('klasse');
        $geschlecht = $request->query->get('geschlecht');
        $altersklasse = $request->query->get('altersklasse');
        $disziplin = $request->query->get('disziplin');

        // -----------------------------
        // Teilnehmer laden
        // -----------------------------
        $query = "
            SELECT p.*, u.auxinfo AS klasse
            FROM sportabzeichen_participants p
            LEFT JOIN users u ON p.import_id = u.importid
            WHERE 1=1
        ";

        $params = [];
        if ($geschlecht) {
            $query .= " AND (p.geschlecht = :geschlecht OR 
                            (p.geschlecht = 'm' AND :geschlecht = 'MALE') OR 
                            (p.geschlecht = 'w' AND :geschlecht = 'FEMALE'))";
            $params['geschlecht'] = $geschlecht;
        }

        if ($klasse) {
            $query .= " AND u.auxinfo = :klasse";
            $params['klasse'] = $klasse;
        }

        $participants = $db->fetchAllAssociative($query, $params);

        // -----------------------------
        // Disziplinen laden (Requirements)
        // -----------------------------
        $reqData = $db->fetchAllAssociative("
            SELECT disziplin, kategorie, auswahlnummer
            FROM sportabzeichen_requirements
            WHERE jahr = :jahr
            ORDER BY kategorie, auswahlnummer
        ", ['jahr' => $jahr % 100]);

        $disciplineOptions = [
            'ENDURANCE' => [],
            'FORCE' => [],
            'COORDINATION' => [],
            'RAPIDNESS' => [],
        ];

        foreach ($reqData as $row) {
            $cat = strtoupper($row['kategorie']);
            if (isset($disciplineOptions[$cat])) {
                $disciplineOptions[$cat][] = [
                    'name' => $row['disziplin'],
                    'sort' => (int)$row['auswahlnummer'],
                ];
            }
        }

        foreach ($disciplineOptions as &$list) {
            usort($list, fn($a, $b) => $a['sort'] <=> $b['sort']);
        }

        // Klassen für Filter (aus User-Daten)
        $klassen = $db->fetchFirstColumn("SELECT DISTINCT auxinfo FROM users WHERE auxinfo IS NOT NULL ORDER BY auxinfo");

       foreach ($participants as &$p) {
       // Wert absichern und in String wandeln
          $geschlechtRaw = $p['geschlecht'] ?? '';
          $geschlecht = strtolower(trim((string)$geschlechtRaw));

          $p['geschlecht_text'] = match ($geschlecht) {
             'm', 'male' => 'Männlich',
             'w', 'female' => 'Weiblich',
             default => 'Unbekannt',
          };
       }

        return $this->render('@PulsRSportabzeichen/exams/index.html.twig', [
            'jahr' => $jahr,
            'participants' => $participants,
            'disciplineOptions' => $disciplineOptions,
            'klassen' => $klassen,
            'selectedKlasse' => $klasse,
            'selectedGeschlecht' => $geschlecht,
            'selectedAltersklasse' => $altersklasse,
            'selectedDisziplin' => $disziplin,
        ]);
    }

    // -----------------------------
    // Speicherung der Werte
    // -----------------------------
    #[Route('/sportabzeichen/exams/save', name: 'sportabzeichen_exams_save', methods: ['POST'])]
    public function save(Request $request, Connection $db): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Leere oder ungültige Daten.'], 400);
        }

        $jahr = (int) date('Y');
        $count = 0;

        foreach ($data as $entry) {
            $participant_id = $entry['participant_id'] ?? null;
            if (!$participant_id) {
                continue;
            }

            $db->executeStatement("
                INSERT INTO sportabzeichen_exams (
                    participant_id, jahr, klasse,
                    koordination_disziplin, koordination_wert,
                    ausdauer_disziplin, ausdauer_wert,
                    schnelligkeit_disziplin, schnelligkeit_wert,
                    kraft_disziplin, kraft_wert,
                    schwimmnachweis, gesamtpunkte, updated_at
                ) VALUES (
                    :participant_id, :jahr, :klasse,
                    :koordination_disziplin, :koordination_wert,
                    :ausdauer_disziplin, :ausdauer_wert,
                    :schnelligkeit_disziplin, :schnelligkeit_wert,
                    :kraft_disziplin, :kraft_wert,
                    :schwimmnachweis, :gesamtpunkte, NOW()
                )
                ON CONFLICT (participant_id, jahr)
                DO UPDATE SET
                    klasse = EXCLUDED.klasse,
                    koordination_disziplin = EXCLUDED.koordination_disziplin,
                    koordination_wert = EXCLUDED.koordination_wert,
                    ausdauer_disziplin = EXCLUDED.ausdauer_disziplin,
                    ausdauer_wert = EXCLUDED.ausdauer_wert,
                    schnelligkeit_disziplin = EXCLUDED.schnelligkeit_disziplin,
                    schnelligkeit_wert = EXCLUDED.schnelligkeit_wert,
                    kraft_disziplin = EXCLUDED.kraft_disziplin,
                    kraft_wert = EXCLUDED.kraft_wert,
                    schwimmnachweis = EXCLUDED.schwimmnachweis,
                    gesamtpunkte = EXCLUDED.gesamtpunkte,
                    updated_at = NOW()
            ", [
                'participant_id' => $participant_id,
                'jahr' => $jahr,
                'klasse' => $entry['klasse'] ?? null,
                'koordination_disziplin' => $entry['koordination_disziplin'] ?? null,
                'koordination_wert' => $entry['koordination_wert'] ?? null,
                'ausdauer_disziplin' => $entry['ausdauer_disziplin'] ?? null,
                'ausdauer_wert' => $entry['ausdauer_wert'] ?? null,
                'schnelligkeit_disziplin' => $entry['schnelligkeit_disziplin'] ?? null,
                'schnelligkeit_wert' => $entry['schnelligkeit_wert'] ?? null,
                'kraft_disziplin' => $entry['kraft_disziplin'] ?? null,
                'kraft_wert' => $entry['kraft_wert'] ?? null,
                'schwimmnachweis' => !empty($entry['schwimmnachweis']),
                'gesamtpunkte' => $entry['gesamtpunkte'] ?? 0,
            ]);

            $count++;
        }

        return new JsonResponse(['message' => "✅ $count Datensätze gespeichert."]);
    }
}
