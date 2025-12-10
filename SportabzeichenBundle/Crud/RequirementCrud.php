<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenRequirement;

class RequirementCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenRequirement::class;

    protected function configure(): void
    {
        $this->title = _('Anforderungen');
        $this->itemTitle = _('Anforderung');
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('disziplin')
            ->add('kategorie')
            ->add('jahr')
            ->add('altersklasse')
            ->add('geschlecht')
            ->add('auswahlnummer');
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('jahr')
            ->add('altersklasse')
            ->add('geschlecht')
            ->add('auswahlnummer')
            ->add('disziplin')
            ->add('kategorie')
            ->add('bronze')
            ->add('silber')
            ->add('gold')
            ->add('einheit')
            ->add('schwimmnachweis')
            ->add('berechnungsart');
    }
}
