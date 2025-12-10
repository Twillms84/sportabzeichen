<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenDiscipline;

class SportabzeichenDisciplineCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenDiscipline::class;

    protected function configure(): void
    {
        $this->title = _('Disziplinen');
        $this->itemTitle = _('Disziplin');
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('kategorie')
            ->add('einheit')
            ->add('berechnungsart')
            ->add('createdAt');
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name')
            ->add('kategorie')
            ->add('einheit')
            ->add('berechnungsart');
    }
    
}
