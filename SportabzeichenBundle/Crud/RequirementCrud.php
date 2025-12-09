<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenRequirement;

class RequirementCrud extends ServiceCrud
{
    // WICHTIG: kein Typ angeben, static MUSS sein!
    protected static $entityClass = SportabzeichenRequirement::class;

    protected function configure(): void
    {
        $this->title = _('Anforderungen');
        $this->itemTitle = _('Anforderung');
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('disziplin', null, ['label' => _('Disziplin')])
            ->add('kategorie', null, ['label' => _('Kategorie')])
            ->add('jahr', null, ['label' => _('Jahr')])
            ->add('altersklasse', null, ['label' => _('Altersklasse')])
            ->add('geschlecht', null, ['label' => _('Geschlecht')])
            ->add('auswahlnummer', null, ['label' => _('AW-Nr.')]);
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('jahr', null, ['label' => _('Jahr')])
            ->add('altersklasse', null, ['label' => _('Altersklasse')])
            ->add('geschlecht', null, ['label' => _('Geschlecht')])
            ->add('auswahlnummer', null, ['label' => _('Auswahlnummer')])
            ->add('disziplin', null, ['label' => _('Disziplin')])
            ->add('kategorie', null, ['label' => _('Kategorie')])
            ->add('bronze', null, ['label' => _('Bronze')])
            ->add('silber', null, ['label' => _('Silber')])
            ->add('gold', null, ['label' => _('Gold')])
            ->add('einheit', null, ['label' => _('Einheit')])
            ->add('schwimmnachweis', null, ['label' => _('Schwimmnachweis')])
            ->add('berechnungsart', null, ['label' => _('Berechnungsart')]);
    }
}
