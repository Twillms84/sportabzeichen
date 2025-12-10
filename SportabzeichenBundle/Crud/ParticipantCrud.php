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
        // keine Add/Delete? hängt von dir ab
        // $this->disableAdd();
        // $this->disableDelete();
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
        $readonly = ['disabled' => true];

        $form
            ->add('importId', null, $readonly)
            ->add('vorname', null, $readonly)
            ->add('nachname', null, $readonly)
            ->add('geschlecht', null, $readonly)
            ->add('geburtsdatum', null, $readonly);
    }

    public function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('importId')
            ->add('vorname')
            ->add('nachname')
            ->add('geschlecht')
            ->add('geburtsdatum')
            ->add('updatedAt');
    }
}
