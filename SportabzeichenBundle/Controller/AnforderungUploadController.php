<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Upload + Import der DOSB-Anforderungstabellen als CSV.
 * CSV-Felder werden 1:1 eingelesen und korrekt auf das neue relationale Schema abgebildet.
 */
#[Route(path: '/sportabzeichen/admin', name: 'sportabzeichen_admin_')]
final class AnforderungUploadController extends AbstractPageController
{
    #[Route(path: '/upload', name: 'upload')]
    public function upload(Request $request, Connection $conn): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        $message = null;
        $error = null;
        $importCount = 0;
        $skipCount = 0;

        // --- Logging
        $logDir = '/var/lib/iserv/sportabzeichen/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $debugLog = $logDir . '/import_debug.log';
        file_put_contents($debugLog, "\n=== Neuer Import: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        if ($request->isMethod('POST')) {
            $file = $request->files->get('csvFile');

            if (!$file) {
                $error = 'Keine Datei ausgewählt.';
            } elseif ($file->getClientOriginalExtension() !== 'csv') {
                $error = 'Nur CSV-Dateien erlaubt.';
            } else {
                $tmpPath = $file->getRealPath();

                try {
                    if (($handle = fopen($tmpPath, 'r')) !== false) {
                        $delimiter = ',';

                        // Encoding erkennen
                        $sample = fread($handle, 4096);
                        rewind($handle);
                        $encoding = mb_detect_encoding(
                            $sample,
                            ['UTF-8', 'ISO-8859-1', 'Windows-1252'],
                            true
                        );

                        $convert = fn(array $row) =>
                            array_map(
                                fn($v) =>
                                    $encoding && $encoding !== 'UTF-8'
                                        ? mb_convert_encoding($v, 'UTF-8', $encoding)
                                        : $v,
                                $row
                            );

                        // Kopfzeile überspringen
                        fgetcsv($handle, 0, $delimiter);

                        // --- SQL vorbereitet ---

                        // Disziplin finden
                        $sqlFindDiscipline = "
                            SELECT id FROM sportabzeichen_disciplines
                            WHERE name = :name 
                              AND kategorie = :kat 
                              AND einheit = :einheit 
                              AND berechnungsart = :art
                            LIMIT 1
                        ";

                        // Disziplin einfügen
                        $sqlInsertDiscipline = "
                            INSERT INTO sportabzeichen_disciplines 
                                (name, kategorie, einheit, berechnungsart)
                            VALUES 
                                (:name, :kat, :einheit, :art)
                            RETURNING id
                        ";

                        // Requirements einfügen/updaten
                        $sqlInsertRequirement = "
                            INSERT INTO sportabzeichen_requirements
                                (discipline_id, jahr, altersklasse, geschlecht, bronze, silber, gold, schwimmnachweis)
                            VALUES
                                (:discipline_id, :jahr, :altersklasse, :geschlecht, :bronze, :silber, :gold, :schwimmnachweis)
                            ON CONFLICT (discipline_id, jahr, altersklasse, geschlecht)
                            DO UPDATE SET
                                bronze = EXCLUDED.bronze,
                                silber = EXCLUDED.silber,
                                gold = EXCLUDED.gold,
                                schwimmnachweis = EXCLUDED.schwimmnachweis
                        ";

                        $stmtFindDisc = $conn->prepare($sqlFindDiscipline);
                        $stmtInsertDisc = $conn->prepare($sqlInsertDiscipline);
                        $stmtInsertReq = $conn->prepare($sqlInsertRequirement);

                        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                            $row = $convert(array_map(fn($v) => trim($v, " \t\n\r\0\x0B\""), $row));

                            if (count($row) < 14) {
                                file_put_contents($debugLog, "⚠️ Zu wenige Spalten: " . json_encode($row) . "\n", FILE_APPEND);
                                $skipCount++;
                                continue;
                            }

                            // --- CSV auslesen ---
                            $jahr           = (int)$row[1];
                            $altersklasse   = $row[2];
                            $geschlecht     = strtoupper($row[3]);   // m/w/d
                            $disziplin      = $row[5];
                            $kategorie      = strtoupper($row[6]);
                            $map = [
                                'ENDURANCE'    => 'Ausdauer',
                                'FORCE'        => 'Kraft',
                                'RAPIDNESS'    => 'Schnelligkeit',
                                'COORDINATION' => 'Koordination',
                                'SWIMMING'     => 'Schwimmen'
                            ];
                            $kategorieMapped = $map[$kategorie] ?? $kategorie;
                            $bronze         = $row[7] !== '' ? (float)$row[7] : null;
                            $silber         = $row[8] !== '' ? (float)$row[8] : null;
                            $gold           = $row[9] !== '' ? (float)$row[9] : null;
                            $einheit        = $row[11];
                            $schwimm        = in_array(strtolower($row[12]), ['1', 'true', 'yes', 'y'], true);
                            $berechnungsart = strtoupper($row[13] ?: 'GREATER');

                            // --- 1. Disziplin suchen ---
                            $stmtFindDisc->execute([
                                'name' => $disziplin,
                                'kat' => $kategorie,
                                'einheit' => $einheit,
                                'art' => $berechnungsart,
                            ]);

                            $disciplineId = $stmtFindDisc->fetchOne();

                            // --- 2. Falls nicht vorhanden → anlegen ---
                            if (!$disciplineId) {
                                $stmtInsertDisc->execute([
                                    'name' => $disziplin,
                                    'kat' => $kategorieMapped
                                    'einheit' => $einheit,
                                    'art' => $berechnungsart,
                                ]);
                                $disciplineId = $stmtInsertDisc->fetchOne();
                            }

                            // --- 3. Requirement einfügen/updaten ---
                            try {
                                $stmtInsertReq->execute([
                                    'discipline_id' => $disciplineId,
                                    'jahr' => $jahr,
                                    'altersklasse' => $altersklasse,
                                    'geschlecht' => $geschlecht,
                                    'bronze' => $bronze,
                                    'silber' => $silber,
                                    'gold' => $gold,
                                    'schwimmnachweis' => $schwimm,
                                ]);

                                $importCount++;
                            } catch (\Throwable $e) {
                                $skipCount++;
                                file_put_contents($debugLog, "❌ SQL-Fehler: {$e->getMessage()}\n", FILE_APPEND);
                            }
                        }

                        fclose($handle);
                    }

                    $message = sprintf(
                        '✅ Import abgeschlossen: %d Zeilen übernommen, %d übersprungen.',
                        $importCount,
                        $skipCount
                    );
                } catch (FileException $e) {
                    $error = 'Fehler beim Verarbeiten der Datei: ' . $e->getMessage();
                }
            }
        }

        return $this->render('@PulsRSportabzeichen/admin/upload.html.twig', [
            'title' => _('Disziplinanforderungen hochladen'),
            'message' => $message,
            'error' => $error,
        ]);
    }
}
