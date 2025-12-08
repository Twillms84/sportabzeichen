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
 * Upload und direkter Import der Disziplinanforderungen in die sportabzeichen_requirements-Tabelle.
 * Unterstützt Encoding-Erkennung (UTF-8 / Windows-1252) und robustes Error-Handling.
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

        // Logverzeichnis vorbereiten
        $logDir = '/var/lib/iserv/sportabzeichen/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $debugLog = $logDir . '/import_debug.log';
        file_put_contents($debugLog, "\n=== Neuer Import gestartet: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        if ($request->isMethod('POST')) {
            $file = $request->files->get('csvFile');

            if (!$file) {
                $error = 'Keine Datei ausgewählt.';
            } elseif ($file->getClientOriginalExtension() !== 'csv') {
                $error = 'Nur CSV-Dateien sind erlaubt.';
            } else {
                $tmpPath = $file->getRealPath();

                try {
                    if (($handle = fopen($tmpPath, 'r')) !== false) {
                        $delimiter = ',';

                        // --- Encoding erkennen ---
                        $sample = fread($handle, 4096);
                        rewind($handle);
                        $encoding = mb_detect_encoding($sample, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                        if ($encoding && $encoding !== 'UTF-8') {
                            file_put_contents($debugLog, "🔤 CSV-Encoding erkannt: {$encoding}, wird konvertiert nach UTF-8\n", FILE_APPEND);
                        } else {
                            file_put_contents($debugLog, "🔤 CSV-Encoding erkannt: UTF-8\n", FILE_APPEND);
                        }

                        $convertToUtf8 = function (array $row) use ($encoding) {
                            return array_map(
                                fn($v) => $encoding && $encoding !== 'UTF-8'
                                    ? mb_convert_encoding($v, 'UTF-8', $encoding)
                                    : $v,
                                $row
                            );
                        };

                        // Kopfzeile überspringen
                        fgetcsv($handle, 0, $delimiter);

                        // SQL vorbereiten
                        $sql = '
                            INSERT INTO sportabzeichen_requirements
                                (nummer, jahr, altersklasse, geschlecht, auswahlnummer, disziplin, kategorie,
                                 bronze, silber, gold, abzeichen, einheit, schwimmnachweis, berechnungsart)
                            VALUES
                                (:nummer, :jahr, :altersklasse, :geschlecht, :auswahlnummer, :disziplin, :kategorie,
                                 :bronze, :silber, :gold, :abzeichen, :einheit, :schwimmnachweis, :berechnungsart)
                            ON CONFLICT (jahr, nummer)
                            DO UPDATE SET
                                altersklasse = EXCLUDED.altersklasse,
                                geschlecht = EXCLUDED.geschlecht,
                                auswahlnummer = EXCLUDED.auswahlnummer,
                                disziplin = EXCLUDED.disziplin,
                                kategorie = EXCLUDED.kategorie,
                                bronze = EXCLUDED.bronze,
                                silber = EXCLUDED.silber,
                                gold = EXCLUDED.gold,
                                abzeichen = EXCLUDED.abzeichen,
                                einheit = EXCLUDED.einheit,
                                schwimmnachweis = EXCLUDED.schwimmnachweis,
                                berechnungsart = EXCLUDED.berechnungsart;
                        ';

                        $stmt = $conn->prepare($sql);

                        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                            $data = $convertToUtf8(array_map(fn($v) => trim($v, " \t\n\r\0\x0B\""), $data));

                            if (count($data) < 14) {
                                $skipCount++;
                                file_put_contents($debugLog, "⚠️ Zeile übersprungen (zu wenige Spalten): " . json_encode($data) . "\n", FILE_APPEND);
                                continue;
                            }

                            // Werte prüfen und konvertieren
                            $bronze = $data[7] === '' ? null : (float)$data[7];
                            $silber = $data[8] === '' ? null : (float)$data[8];
                            $gold = $data[9] === '' ? null : (float)$data[9];

                            // Boolean robust mappen
                            $schwimmnachweis = false;
                            if (!empty($data[12])) {
                                $boolVal = strtolower(trim($data[12]));
                                $schwimmnachweis = in_array($boolVal, ['true', '1', 'yes', 'y', 't', 'wahr'], true);
                            }

                            try {
                                $stmt->bindValue('nummer', (int)$data[0]);
                                $stmt->bindValue('jahr', (int)$data[1]);
                                $stmt->bindValue('altersklasse', $data[2]);
                                $stmt->bindValue('geschlecht', strtoupper($data[3]));
                                $stmt->bindValue('auswahlnummer', (int)$data[4]);
                                $stmt->bindValue('disziplin', $data[5]);
                                $stmt->bindValue('kategorie', strtoupper($data[6]));
                                $stmt->bindValue('bronze', $bronze);
                                $stmt->bindValue('silber', $silber);
                                $stmt->bindValue('gold', $gold);
                                $stmt->bindValue('abzeichen', $data[10] !== '' ? $data[10] : null);
                                $stmt->bindValue('einheit', $data[11] !== '' ? $data[11] : null);
                                $stmt->bindValue('schwimmnachweis', (bool)$schwimmnachweis, \PDO::PARAM_BOOL);
                                $stmt->bindValue('berechnungsart', $data[13] !== '' ? strtoupper($data[13]) : null);

                                $stmt->execute();
                                $importCount++;
                            } catch (\Throwable $e) {
                                $skipCount++;
                                file_put_contents($debugLog, "❌ SQL-Fehler Zeile {$importCount}: " . $e->getMessage() . "\n", FILE_APPEND);
                            }
                        }

                        fclose($handle);
                    }

                    $message = sprintf(
                        '✅ Import abgeschlossen: %d Datensätze importiert, %d Zeilen übersprungen.',
                        $importCount,
                        $skipCount
                    );

                    file_put_contents($debugLog, $message . "\n=== Import beendet ===\n", FILE_APPEND);
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
