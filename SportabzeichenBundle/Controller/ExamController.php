<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sportabzeichen/exams', name: 'sportabzeichen_exams_')]
final class ExamController extends AbstractController
{
    public function __construct(private Connection $db) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $participants = $this->db->fetchAllAssociative('SELECT * FROM sportabzeichen_participants ORDER BY nachname, vorname');
        $currentYear = (int) date('Y');

        foreach ($participants as &$p) {
            if (!empty($p['geburtsdatum'])) {
                $birth = new \DateTimeImmutable($p['geburtsdatum']);
                $p['alter'] = $currentYear - (int)$birth->format('Y');
                $p['altersklasse'] = $this->getAgeClass($p['alter']);
            } else {
                $p['alter'] = null;
                $p['altersklasse'] = null;
            }

            $p['geschlecht'] = match (strtolower(trim($p['geschlecht'] ?? ''))) {
                'm', 'male' => 'MALE',
                'w', 'female' => 'FEMALE',
                default => 'MALE',
            };

            $p['swim_status'] = false;
            $p['swim_valid_until'] = null;
            $p['swim_icon'] = '❌';
        }
        unset($p);

        // Anforderungen laden
        $reqRaw = $this->db->fetchAllAssociative('SELECT * FROM sportabzeichen_requirements');
        $requirements = [];

        foreach ($reqRaw as $r) {
            $geschlecht = strtoupper(trim($r['geschlecht'] ?? 'MALE'));
            $altersklasse = strtoupper(trim($r['altersklasse'] ?? ''));
            $kategorie = strtoupper(trim($r['kategorie'] ?? 'UNKNOWN'));
            $berechnungsart = strtoupper(trim($r['berechnungsart'] ?? ''));

            if (!$altersklasse) continue;

            $requirements[$geschlecht][$altersklasse][$kategorie][] = [
                'auswahlnummer' => $r['auswahlnummer'] ?? 0,
                'disziplin' => trim($r['disziplin']),
                'bronze' => $r['bronze'],
                'silber' => $r['silber'],
                'gold' => $r['gold'],
                'einheit' => $r['einheit'],
                'berechnungsart' => $berechnungsart,
                'punktefeld' => empty($berechnungsart),
            ];
        }

        // Debug
        file_put_contents(
           '/usr/share/iserv/web/modules/PulsR/SportabzeichenBundle/Resources/logs/sportabzeichen_debug.log',
            print_r([
               'keys_male' => array_keys($requirements['MALE'] ?? []),
               'keys_female' => array_keys($requirements['FEMALE'] ?? []),
               'sample' => $requirements['MALE']['AC0708']['ENDURANCE'][0] ?? null,
            ], true)
 	);


        return $this->render('@PulsRSportabzeichen/exam/index.html.twig', [
            'title' => 'Sportabzeichen Prüfungen',
            'participants' => $participants,
            'requirements' => $requirements,
        ]);
    }

    private function getAgeClass(int $age): string
{
    // Unter 6 Jahren → keine Zuordnung
    if ($age < 6) {
        return 'AC0006';
    }

    // Kinder und Jugendliche: feste 2er-Gruppen bis 19
    $childClasses = [
        [6, 7, 'AC0607'],
        [8, 9, 'AC0809'],
        [10, 11, 'AC1011'],
        [12, 13, 'AC1213'],
        [14, 15, 'AC1415'],
        [16, 17, 'AC1617'],
        [18, 19, 'AC1819'],
    ];

    foreach ($childClasses as $range) {
        if ($age >= $range[0] && $age <= $range[1]) {
            return $range[2];
        }
    }

    // Erwachsene: 5-Jahres-Gruppen
    $adultRanges = [
        [20, 24, 'AC2024'],
        [25, 29, 'AC2529'],
        [30, 34, 'AC3034'],
        [35, 39, 'AC3539'],
        [40, 44, 'AC4044'],
        [45, 49, 'AC4549'],
        [50, 54, 'AC5054'],
        [55, 59, 'AC5559'],
        [60, 64, 'AC6064'],
        [65, 69, 'AC6569'],
        [70, 74, 'AC7074'],
        [75, 79, 'AC7579'],
        [80, 84, 'AC8084'],
        [85, 89, 'AC8589'],
    ];

    foreach ($adultRanges as $range) {
        if ($age >= $range[0] && $age <= $range[1]) {
            return $range[2];
        }
    }

    // Ab 90+
    return 'AC9000';
}


}
