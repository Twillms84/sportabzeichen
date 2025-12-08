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
                default => 'MALE', // Fallback
            };

            $p['swim_status'] = false;
            $p['swim_valid_until'] = null;
            $p['swim_icon'] = '❌';
        }
        unset($p);

        // Anforderungen abrufen
        $reqRaw = $this->db->fetchAllAssociative('SELECT * FROM sportabzeichen_requirements ORDER BY jahr, auswahlnummer');
        $requirements = [];

        foreach ($reqRaw as $r) {
            $geschlecht = match (strtolower($r['geschlecht'] ?? '')) {
                'm', 'male' => 'MALE',
                'w', 'female' => 'FEMALE',
                default => 'MALE',
            };

            $altersklasse = strtoupper(trim($r['altersklasse']));
            $kategorie = strtoupper(trim($r['kategorie'] ?? 'UNKNOWN'));
            $berechnungsart = strtoupper(trim($r['berechnungsart'] ?? ''));

            if ($altersklasse && $kategorie) {
                $requirements[$geschlecht][$altersklasse][$kategorie][] = [
                    'auswahlnummer' => $r['auswahlnummer'] ?? 0,
                    'disziplin' => trim($r['disziplin']),
                    'bronze' => $r['bronze'],
                    'silber' => $r['silber'],
                    'gold' => $r['gold'],
                    'einheit' => $r['einheit'],
                    'berechnungsart' => $berechnungsart,
                    'punktefeld' => empty($r['berechnungsart']),
                ];
            }
        }

        // DEBUG: zeigen, was tatsächlich da ist
        file_put_contents('/tmp/requirements.log', print_r(array_keys($requirements['MALE'] ?? []), true));

        return $this->render('@PulsRSportabzeichen/exam/index.html.twig', [
            'title' => 'Sportabzeichen Prüfungen',
            'participants' => $participants,
            'requirements' => $requirements,
        ]);
    }

    private function getAgeClass(int $age): string
    {
        if ($age < 7) return 'AC0006';
        if ($age <= 25) {
            $start = (int)(floor(($age - 7) / 2) * 2 + 7);
            return sprintf('AC%02d%02d', $start, $start + 1);
        }
        if ($age < 90) {
            $start = (int)(floor(($age - 25) / 5) * 5 + 25);
            return sprintf('AC%02d%02d', $start, $start + 4);
        }
        return 'AC9000';
    }
}
