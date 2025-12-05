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
 * Ermöglicht den Upload und direkten Import von Disziplinanforderungen.
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

        if ($request->isMethod('POST')) {
            $file = $request->files->get('csvFile');
            $jahr = $request->request->get('jahr');

            if (!$file) {
                $error = 'Keine Datei ausgewählt.';
            } elseif ($file->getClientOriginalExtension() !== 'csv') {
                $error = 'Nur CSV-Dateien sind erlaubt.';
            } elseif (!is_numeric($jahr) || strlen($jahr) !== 4) {
                $error = 'Bitte ein gültiges Jahr angeben (z. B. 2025).';
            } else {
                $targetDir = '/var/lib/iserv/sportabzeichen/anforderungen/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                $targetFile = sprintf('%sanforderungen_%s.csv', $targetDir, $jahr);

                try {
                    $file->move($targetDir, basename($targetFile));

                    // --- CSV importieren ---
                    if (($handle = fopen($targetFile, 'r')) !== false) {
                        // Kopfzeile überspringen
                        fgetcsv($handle, 0, ',');

                        $stmt = $conn->prepare('
                            INSERT INTO sportabzeichen_requirements
                                (jahr, altersklasse, geschlecht, auswahlnummer, disziplin, kategorie,
                                 bronze, silber, gold, abzeichen, einheit, schwimmnachweis, berechnungsart)
                            VALUES (:jahr, :altersklasse, :geschlecht, :auswahlnummer, :disziplin, :kategorie,
                                    :bronze, :silber, :gold, :abzeichen, :einheit, :schwimmnachweis, :berechnungsart)
                            ON CONFLICT (jahr, altersklasse, geschlecht, disziplin)
                            DO UPDATE SET
                                bronze = EXCLUDED.bronze,
                                silber = EXCLUDED.silber,
                                gold = EXCLUDED.gold,
                                einheit = EXCLUDED.einheit;
                        ');

                        while (($data = fgetcsv($handle, 0, ',')) !== false) {
                            // Sicherheitsprüfung: richtige Spaltenanzahl (mind. 14)
                            if (count($data) < 14) {
                                continue;
                            }

                            $stmt->execute([
                                'jahr' => (int)$data[1],
                                'altersklasse' => trim($data[2]),
                                'geschlecht' => trim($data[3]),
                                'auswahlnummer' => (int)$data[4],
                                'disziplin' => trim($data[5]),
                                'kategorie' => trim($data[6]),
                                'bronze' => (float)$data[7],
                                'silber' => (float)$data[8],
                                'gold' => (float)$data[9],
                                'abzeichen' => $data[10] ?? null,
                                'einheit' => $data[11] ?? null,
                                'schwimmnachweis' => filter_var($data[12], FILTER_VALIDATE_BOOLEAN),
                                'berechnungsart' => $data[13] ?? null,
                            ]);

                            $importCount++;
                        }
                        fclose($handle);
                    }

                    $message = sprintf(
                        '✅ Datei "%s" erfolgreich importiert – %d Datensätze hinzugefügt/aktualisiert.',
                        basename($targetFile),
                        $importCount
                    );
                } catch (FileException $e) {
                    $error = 'Fehler beim Speichern: ' . $e->getMessage();
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
