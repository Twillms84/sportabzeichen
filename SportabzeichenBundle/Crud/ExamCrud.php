<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Doctrine\ORM\ORMObjectManager;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExam;

class ExamCrud extends ServiceCrud
{
    // WICHTIG: für IServ-Routing erforderlich!
    protected static $entityClass = SportabzeichenExam::class;

    public function __construct(ORMObjectManager $objectManager)
    {
        // WICHTIG: für Legacy-ServiceCrud erforderlich!
        $this->class = SportabzeichenExam::class;

        parent::__construct($objectManager);
    }

    protected function configure(): void
    {
        $this->title = _('Prüfungen');
        $this->itemTitle = _('Prüfung');
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('examName')
            ->add('examDate')
            ->add('examYear')
            ->add('createdAt');
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('examName')
            ->add('examDate')
            ->add('examYear');
    }
}
