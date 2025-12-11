<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\AbstractCrud;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;

use IServ\CrudBundle\Table\Column\DateColumn;
use IServ\CrudBundle\Table\Column\NumberColumn;
use IServ\CrudBundle\Table\Column\TextColumn;
use IServ\CrudBundle\Table\Action\Row\LinkAction;

use IServ\CrudBundle\Table\Action\Row\ActionButton;
use IServ\CrudBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use PulsR\SportabzeichenBundle\Entity\SportabzeichenExam;

class SportabzeichenExamCrud extends ServiceCrud
{
    public function getEntityClass(): string
    {
        return SportabzeichenExam::class;
    }

    public function getTitle(): string
    {
        return 'Prüfungen';
    }

    public function configure(): void
    {
        // -------------------------------------------------------
        // Tabellenübersicht
        // -------------------------------------------------------
        $this->addColumn('id', NumberColumn::class, ['label' => 'ID']);
        $this->addColumn('examName', TextColumn::class, ['label' => 'Bezeichnung']);
        $this->addColumn('examDate', DateColumn::class, [
            'label' => 'Datum',
            'format' => 'd.m.Y'
        ]);
        $this->addColumn('examYear', NumberColumn::class, ['label' => 'Jahr']);

        // Teilnehmer-Zähler per Callback
        $this->addColumn('examParticipants', TextColumn::class, [
            'label' => 'Teilnehmer',
            'callback' => fn ($exam) => count($exam->getExamParticipants()),
        ]);

        // -------------------------------------------------------
        // Aktionen in der Zeile
        // -------------------------------------------------------
        $this->addRowAction(new LinkAction(
            'Teilnehmer',
            fn ($exam) => $this->router->generate(
                'sportabzeichen_exam_participants',
                ['id' => $exam->getId()]
            ),
            'fa fa-users'
        ));

        $this->addRowAction(new LinkAction(
            'Ergebnisse',
            fn ($exam) => $this->router->generate(
                'sportabzeichen_exam_participants',
                ['id' => $exam->getId()]
            ),
            'fa fa-pencil-alt'
        ));

        // Standard-Aktionen (Bearbeiten & Löschen)
        $this->addEditAction();
        $this->addDeleteAction();
    }

    // -------------------------------------------------------
    // Formular Definition
    // -------------------------------------------------------
public function configureFormFields(FormMapper $formMapper): void
{
    $formMapper->add('examName', TextType::class, [
        'label' => 'Bezeichnung',
        'required' => true,
    ]);

    $formMapper->add('examDate', DatePickerType::class, [
        'label' => 'Prüfungsdatum',
        'required' => false,
    ]);

    $formMapper->add('examYear', IntegerType::class, [
        'label' => 'Jahr',
        'required' => true,
    ]);
}

    // -------------------------------------------------------
    // Detailseite
    // -------------------------------------------------------
public function configureShowFields(ShowMapper $showMapper): void
{
    $showMapper->add('id');
    $showMapper->add('examName', null, ['label' => 'Bezeichnung']);
    $showMapper->add('examDate', null, ['label' => 'Datum']);
    $showMapper->add('examYear', null, ['label' => 'Jahr']);
    $showMapper->add('createdAt', null, ['label' => 'Erstellt am']);
    $showMapper->add('updatedAt', null, ['label' => 'Aktualisiert am']);
}

}
