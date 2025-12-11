<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Controller;

use Doctrine\DBAL\Connection;
use IServ\CoreBundle\Controller\AbstractPageController;
use IServ\Library\User\User;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExam;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExamParticipant;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExamResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sportabzeichen/exam', name: 'sportabzeichen_results_')]
final class ExamResultController extends AbstractPageController
{
    #[Route('/{id}/results', name: 'index')]
    public function list(
        SportabzeichenExam $exam,
        Request $request,
        Connection $conn
    ): Response
    {
        $this->denyAccessUnlessGranted('PRIV_SPORTABZEICHEN_MANAGE');

        // Filter
        $filterClass = $request->query->get('class');
        $filterGender = $request->query->get('gender');
        $filterAge = $request->query->get('age');

        // Alle Teilnehmer des Exams
        $query = "
            SELECT ep.id AS ep_id, p.id AS pid, p.vorname, p.nachname, p.geschlecht, ep.age_year
            FROM sportabzeichen_exam_participants ep
            JOIN sportabzeichen_participants p ON p.id = ep.participant_id
        ";

        $conditions = [];
        $params = [];

        if ($filterGender) {
            $conditions[] = "p.geschlecht = :g";
            $params['g'] = $filterGender;
        }

        if ($filterAge) {
            $conditions[] = "ep.age_year = :a";
            $params['a'] = (int)$filterAge;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $participants = $conn->fetchAllAssociative($query, $params);

        // Liste aller Klassen aus IServ Userdatenbank
        $classes = $conn->fetchFirstColumn("
            SELECT DISTINCT class FROM users WHERE class IS NOT NULL ORDER BY class
        ");

        return $this->render('@PulsRSportabzeichen/results/index.html.twig', [
            'exam'        => $exam,
            'participants'=> $participants,
            'classes'     => $classes,
            'filters'     => [
                'class' => $filterClass,
                'gender'=> $filterGender,
                'age'   => $filterAge,
            ]
        ]);
    }
