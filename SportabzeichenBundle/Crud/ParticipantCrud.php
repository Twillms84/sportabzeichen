<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenParticipant;

class ParticipantCrud extends ServiceCrud
{
    // WICHTIG: kein Typ, static erforderlich
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
            ->add('geburtsdatum');
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('vorname')
            ->add('nachname')
            ->add('geschlecht')
            ->add('geburtsdatum')
            ->add('importId');
    }
}
