<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\FilterMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenRequirement;

class SportabzeichenRequirementCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenRequirement::class;

    protected function configure(): void
    {
        $this->title = _('Anforderungen');
        $this->itemTitle = _('Anforderung');

        // *** CSV-Import steuert die Daten → keine CRUD-Aktionen ***
        $this->canAdd = false;
        $this->canEdit = false;
        $this->canDelete = false;
    }

    /**
     * Felder für die Listenansicht
     */
    public function configureListFields(ListMapper $list): void
    {
        $list
            ->add('discipline.name', null, [
                'label' => _('Disziplin')
            ])
            ->add('discipline.kategorie', null, [
                'label' => _('Kategorie')
            ])
            ->add('jahr', null, [
                'label' => _('Jahr')
            ])
            ->add('altersklasse', null, [
                'label' => _('Altersklasse')
            ])
            ->add('geschlecht', null, [
                'label' => _('Geschlecht')
            ])
            ->add('bronze', null, [
                'label' => _('Bronze')
            ])
            ->add('silber', null, [
                'label' => _('Silber')
            ])
            ->add('gold', null, [
                'label' => _('Gold')
            ])
            ->add('schwimmnachweis', null, [
                'label' => _('Schwimmnachweis')
            ]);
    }

    /**
     * Filter für die Tabelle
     */
    public function configureFilterFields(FilterMapper $filter): void
    {
        $filter
            ->add('jahr', null, [
                'label' => _('Jahr')
            ])
            ->add('geschlecht', null, [
                'label' => _('Geschlecht')
            ])
            ->add('altersklasse', null, [
                'label' => _('Altersklasse')
            ])
            ->add('discipline.kategorie', null, [
                'label' => _('Kategorie')
            ])
            ->add('discipline.name', null, [
                'label' => _('Disziplin')
            ]);
    }
}
