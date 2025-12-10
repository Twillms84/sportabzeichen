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

        $msg = null;
        $error = null;
        $imported = 0;
        $skipped = 0;

        $logFile = '/var/lib/iserv/sportabzeichen/logs/requirements_import.log';
        @mkdir(dirname($logFile), 0775, true);

        file_put_contents($logFile, "=== Import " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        if ($request->isMethod('POST')) {

            $file = $request->files->get('csvFile');
            if (!$file) {
                return $this->renderError("Keine CSV ausgewählt.");
            }

            $path = $file->getRealPath();
            $handle = fopen($path, 'r');

            if (!$handle) {
                return $this->renderError("CSV konnte nicht geöffnet werden.");
            }

            // Encoding erkennen
            $sample = fread($handle, 4096);
            rewind($handle);
            $enc = mb_detect_encoding($sample, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
            file_put_contents($logFile, "Encoding: $enc\n", FILE_APPEND);

            $convert = function ($row) use ($enc) {
                return array_map(function ($v) use ($enc) {
                    $v = trim($v, " \t\n\r\0\x0B\"");
                    return $enc !== 'UTF-8'
                        ? mb_convert_encoding($v, 'UTF-8', $enc)
                        : $v;
                }, $row);
            };

            // Kopfzeile
            fgetcsv($handle, 0, ',');

            while (($row = fgetcsv($handle, 0, ',')) !== false) {

                $row = $convert($row);

                if (count($row) < 14) {
                    $skipped++;
                    file_put_contents($logFile, "SKIP – zu wenige Spalten: " . json_encode($row) . "\n", FILE_APPEND);
                    continue;
                }

                try {
                    // Felder extrahieren
                    $nummer        = (int)$row[0]; // wir nutzen nummer NICHT mehr, aber lesen sie aus
                    $jahr          = (int)$row[1];
                    $altersklasse  = $row[2];
                    $geschlecht    = strtoupper($row[3]);
                    $auswahl       = (int)$row[4];
                    $disziplinName = $row[5];
                    $catCode       = strtoupper($row[6]);
                    $bronze        = $row[7] !== '' ? (float)$row[7] : null;
                    $silber        = $row[8] !== '' ? (float)$row[8] : null;
                    $gold          = $row[9] !== '' ? (float)$row[9] : null;
                    $einheit       = $row[11] ?: null;
                    $sn            = strtolower($row[12]) === 'true';
                    $berechnung    = strtoupper($row[13] ?: 'GREATER');

                    // Kategorie mappen
                    $kategorie = self::CATEGORY_MAP[$catCode] ?? $catCode;

                    // 1. Disziplin holen oder anlegen
                    $disciplineId = $conn->executeQuery(
                        "SELECT id FROM sportabzeichen_disciplines WHERE name = ?",
                        [$disziplinName]
                    )->fetchColumn();

                    if (!$disciplineId) {
                        $conn->insert("sportabzeichen_disciplines", [
                            'name'           => $disziplinName,
                            'kategorie'      => $kategorie,
                            'einheit'        => $einheit ?: '',
                            'berechnungsart' => $berechnung
                        ]);
                        $disciplineId = $conn->lastInsertId();
                    }

                    // 2. Requirement speichern
                    $conn->executeStatement("
                        INSERT INTO sportabzeichen_requirements
                            (discipline_id, jahr, altersklasse, geschlecht, bronze, silber, gold, schwimmnachweis)
                        VALUES
                            (:d, :jahr, :ak, :g, :br, :si, :go, :sn)
                        ON CONFLICT (discipline_id, jahr, altersklasse, geschlecht)
                        DO UPDATE SET
                            bronze = EXCLUDED.bronze,
                            silber = EXCLUDED.silber,
                            gold = EXCLUDED.gold,
                            schwimmnachweis = EXCLUDED.schwimmnachweis
                    ", [
                        'd'   => $disciplineId,
                        'jahr'=> $jahr,
                        'ak'  => $altersklasse,
                        'g'   => $geschlecht,
                        'br'  => $bronze,
                        'si'  => $silber,
                        'go'  => $gold,
                        'sn'  => $sn,
                    ]);

                    $imported++;

                } catch (\Throwable $e) {
                    $skipped++;
                    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }

            fclose($handle);

            $msg = "Import abgeschlossen: $imported importiert, $skipped übersprungen.";
        }

        return $this->render('@PulsRSportabzeichen/admin/upload.html.twig', [
            'title' => "Anforderungen-Upload",
            'message' => $msg,
            'error' => $error
        ]);
    }

    private function renderError(string $msg): Response
    {
        return $this->render('@PulsRSportabzeichen/admin/upload.html.twig', [
            'title' => 'Anforderungen-Upload',
            'error' => $msg
        ]);
    }
}
