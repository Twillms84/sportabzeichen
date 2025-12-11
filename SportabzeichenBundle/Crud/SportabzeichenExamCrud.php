<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExam;

class SportabzeichenExamCrud extends ServiceCrud
{
    public function __construct()
    {
        // ServiceCrud braucht die Entity-Klasse im Konstruktor
        parent::__construct(SportabzeichenExam::class);
    }

    /**
     * Grundkonfiguration (Titel, Rechte, etc.)
     */
    protected function configure(): void
    {
        $this->title = _('Prüfungen');
        $this->itemTitle = _('Prüfung');

        $this->canAdd = true;
        $this->canEdit = true;
        $this->canDelete = true;
    }

    /**
     * Spalten in der Listenansicht
     */
    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('examName', null, [
                'label' => _('Name'),
            ])
            ->add('examDate', 'date', [
                'label' => _('Datum'),
            ])
            ->add('examYear', null, [
                'label' => _('Jahr'),
            ])
            ->add('createdAt', 'datetime', [
                'label' => _('Erstellt am'),
            ]);
    }

    /**
     * Felder im Formular (Neu/Bearbeiten)
     */
    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('examName', null, [
                'label' => _('Name der Prüfung'),
            ])
            ->add('examYear', null, [
                'label' => _('Jahr'),
            ])
            ->add('examDate', null, [
                'label' => _('Datum'),
                'required' => false,
            ]);
    }

    /**
     * Felder in der Detailansicht
     */
    public function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('examName', null, ['label' => _('Name')])
            ->add('examYear', null, ['label' => _('Jahr')])
            ->add('examDate', 'date', ['label' => _('Datum')])
            ->add('createdAt', 'datetime', ['label' => _('Erstellt')])
            ->add('updatedAt', 'datetime', ['label' => _('Zuletzt geändert')]);
    }
}
