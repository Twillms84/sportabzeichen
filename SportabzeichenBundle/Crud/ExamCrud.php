<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenExam;

class ExamCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenExam::class;

    protected function configure(): void
    {
        $this->title = _('Prüfungen');
        $this->itemTitle = _('Prüfung');
    }

    public function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('examName')
            ->add('examYear')
            ->add('examDate')
            ->add('createdAt');
    }

    public function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('examName')
            ->add('examYear')
            ->add('examDate');
    }
}
