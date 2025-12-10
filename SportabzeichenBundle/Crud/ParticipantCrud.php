<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenParticipant;

class ParticipantCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenParticipant::class;

    protected function configure(): void
    {
        $this->title = _('Teilnehmer');
        $this->itemTitle = _('Teilnehmer');
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('nachname')
            ->add('vorname')
            ->add('geschlecht')
            ->add('geburtsdatum')
            ->add('importId');
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('importId')
            ->add('vorname')
            ->add('nachname')
            ->add('geschlecht')
            ->add('geschlechtText')
            ->add('geburtsdatum');
    }
    public function configureShowFields(ShowMapper $show): void
    {
    $show
        ->add('importId', null, ['label' => _('Import-ID')])
        ->add('vorname', null, ['label' => _('Vorname')])
        ->add('nachname', null, ['label' => _('Nachname')])
        ->add('geschlecht', null, ['label' => _('Geschlecht (m/w)')])
        ->add('geschlechtText', 'virtual', [
        'label' => _('Geschlecht (Text)'),
        'fieldAccessor' => 'getGeschlechtText',
        ])
        ->add('geburtsdatum', 'date', [
        'label' => _('Geburtsdatum'),
        ])
        ->add('updatedAt', 'datetime', [
        'label' => _('Letzte Aktualisierung'),
        ]);
    }

}

