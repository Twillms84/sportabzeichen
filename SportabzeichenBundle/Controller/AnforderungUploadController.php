<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/sportabzeichen/admin', name: 'sportabzeichen_admin_')]
final class AnforderungUploadController extends AbstractPageController
{
    private const CATEGORY_MAP = [
        'ENDURANCE'    => 'Ausdauer',
        'FORCE'        => 'Kraft',
        'RAPIDNESS'    => 'Schnelligkeit',
        'COORDINATION' => 'Koordination',
        'SWIMMING'     => 'Schwimmen'
    ];

    #[Route(path: '/upload', name: 'upload')]
    public function upload(Request $request, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $message = null;
        $error = null;
        $importCount = 0;
        $skipCount = 0;

        // Logging vorbereiten
        $logDir = '/var/lib/iserv/sportabzeichen/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $debugLog = $logDir . '/requirements_import.log';
        file_put_contents($debugLog, "=== Import gestartet " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        if ($request->isMethod('POST')) {

            $file = $request->files->get('csvFile');
            if (!$file) {
                return $this->renderError("Keine Datei ausgewählt.");
            }

            if ($file->getClientOriginalExtension() !== 'csv') {
                return $this->renderError("Nur CSV erlaubt!");
            }

            $path = $file->getRealPath();
            $handle = fopen($path, 'r');

            if (!$handle) {
                return $this->renderError("Konnte Datei nicht öffnen.");
            }

            // Encoding erkennen
            $sample = fread($handle, 4096);
            rewind($handle);
            $encoding = mb_detect_encoding($sample, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            file_put_contents($debugLog, "Encoding erkannt: $encoding\n", FILE_APPEND);

            $convert = function ($row) use ($encoding) {
                return array_map(function ($v) use ($encoding) {
                    $v = trim($v, " \t\n\r\0\x0B\"");
                    return $encoding !== 'UTF-8' ? mb_convert_encoding($v, 'UTF-8', $encoding) : $v;
                }, $row);
            };

            // Kopfzeile überspringen
            fgetcsv($handle, 0, ',');

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $row = $convert($row);

                // CSV-Spalten prüfen
                if (count($row) < 14) {
                    $skipCount++;
                    file_put_contents($debugLog, "Zu wenige Spalten: " . json_encode($row) . "\n", FILE_APPEND);
                    continue;
                }

                try {
                    $nummer        = (int)$row[0];
                    $jahr          = (int)$row[1];
                    $altersklasse  = $row[2];
                    $geschlecht    = strtoupper($row[3]);
                    $auswahlnummer = (int)$row[4];
                    $disziplinName = $row[5];
                    $kategorieCode = strtoupper($row[6]);
                    $bronze        = $row[7] !== '' ? (float)$row[7] : null;
                    $silber        = $row[8] !== '' ? (float)$row[8] : null;
                    $gold          = $row[9] !== '' ? (float)$row[9] : null;
                    $einheit       = $row[11] ?: null;
                    $schwimmnachw  = strtolower($row[12]) === 'true';
                    $berechnungs   = strtoupper($row[13] ?: 'GREATER');

                    // Kategorie mappen
                    $kategorie = self::CATEGORY_MAP[$kategorieCode] ?? $kategorieCode;

                    // 1. Disziplin sicherstellen
                    $disciplineId = $conn->fetchColumn(
                        "SELECT id FROM sportabzeichen_disciplines WHERE name = ?",
                        [$disziplinName]
                    );

                    if (!$disciplineId) {
                        $conn->insert('sportabzeichen_disciplines', [
                            'name'           => $disziplinName,
                            'kategorie'      => $kategorie,
                            'einheit'        => $einheit ?: '',
                            'berechnungsart' => $berechnungs,
                        ]);
                        $disciplineId = $conn->lastInsertId();
                        file_put_contents($debugLog, "Neue Disziplin: $disziplinName\n", FILE_APPEND);
                    }

                    // 2. Requirement speichern
                    $conn->executeStatement("
                        INSERT INTO sportabzeichen_requirements
                            (discipline_id, jahr, altersklasse, geschlecht, bronze, silber, gold, schwimmnachweis)
                        VALUES
                            (:discipline, :jahr, :ak, :g, :bronze, :silber, :gold, :sn)
                        ON CONFLICT (discipline_id, jahr, altersklasse, geschlecht)
                        DO UPDATE SET
                            bronze = EXCLUDED.bronze,
                            silber = EXCLUDED.silber,
                            gold = EXCLUDED.gold,
                            schwimmnachweis = EXCLUDED.schwimmnachweis
                    ", [
                        'discipline' => $disciplineId,
                        'jahr'       => $jahr,
                        'ak'         => $altersklasse,
                        'g'          => $geschlecht,
                        'bronze'     => $bronze,
                        'silber'     => $silber,
                        'gold'       => $gold,
                        'sn'         => $schwimmnachw,
                    ]);

                    $importCount++;

                } catch (\Throwable $e) {
                    $skipCount++;
                    file_put_contents($debugLog, "Fehler: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }

            fclose($handle);

            $message = "Import abgeschlossen: $importCount Datensätze importiert, $skipCount übersprungen.";
        }

        return $this->render('@PulsRSportabzeichen/admin/upload.html.twig', [
            'title' => "Anforderungen Import",
            'message' => $message,
            'error' => $error
        ]);
    }

    private function renderError(string $msg): Response
    {
        return $this->render('@PulsRSportabzeichen/admin/upload.html.twig', [
            'title' => 'Anforderungen Import',
            'error' => $msg
        ]);
    }
}
