<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\ListMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenRequirement;

class SportabzeichenRequirementCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenRequirement::class;

    protected function configure(): void
    {
        $this->title = _('Anforderungskatalog');
        $this->itemTitle = _('Anforderung');

        // CSV-Import ist Master → Bearbeiten deaktivieren
        $this->disableAdd();
        $this->disableEdit();
        $this->disableDelete();
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('discipline.name', _('Disziplin'))
            ->add('jahr')
            ->add('altersklasse')
            ->add('geschlecht')
            ->add('bronze')
            ->add('silber')
            ->add('gold')
            ->add('schwimmnachweis');
    }
}
