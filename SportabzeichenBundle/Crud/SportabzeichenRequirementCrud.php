<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Crud;

use IServ\CoreBundle\Routing\Link;
use IServ\CrudBundle\Crud\Collection\OrderedCollection;
use IServ\CrudBundle\Crud\ObjectManagerInterface;
use IServ\CrudBundle\Crud\ServiceCrud;
use IServ\CrudBundle\Entity\CrudInterface;
use IServ\CrudBundle\Mapper\FormMapper;
use IServ\CrudBundle\Mapper\ListMapper;
use IServ\CrudBundle\Mapper\ShowMapper;
use IServ\CrudBundle\Table\Filter\ListChoiceFilter;
use IServ\CrudBundle\Table\Filter\ListSearchFilter;
use IServ\CrudBundle\Table\ListHandler;
use PulsR\SportabzeichenBundle\Entity\SportabzeichenRequirement;
use Psr\Container\ContainerInterface;

final class SportabzeichenRequirementCrud extends ServiceCrud
{
    protected static $entityClass = SportabzeichenRequirement::class;

    private ObjectManagerInterface $objectManager;

    public function __construct(ContainerInterface $locator, ObjectManagerInterface $objectManager)
    {
        parent::__construct($locator);
        $this->objectManager = $objectManager;
    }

    protected function configure(): void
    {
        $this->title = _('Disziplinanforderungen');
        $this->itemTitle = _('Disziplinanforderung');
    }

    // ------------------------------------
    // LISTE
    // ------------------------------------
    public function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('disziplin', null, ['label' => _('Disziplin')])
            ->add('kategorie', null, ['label' => _('Kategorie')])
            ->add('jahr', null, ['label' => _('Jahr')])
            ->add('altersklasse', null, ['label' => _('Altersklasse')])
            ->add('geschlecht', null, ['label' => _('Geschlecht')])
            ->add('bronze', null, ['label' => _('Bronze')])
            ->add('silber', null, ['label' => _('Silber')])
            ->add('gold', null, ['label' => _('Gold')])
            ->add('einheit', null, ['label' => _('Einheit')])
            ->add('schwimmnachweis', 'boolean', ['label' => _('Schwimmnachweis')]);
    }

    // ------------------------------------
    // FILTER
    // ------------------------------------
    public function configureListFilter(ListHandler $listHandler): void
    {
        $jahrFilter = new ListChoiceFilter(_('Jahr'), 'jahr', $this->getDistinctValues('jahr'));
        $geschlechtFilter = new ListChoiceFilter(_('Geschlecht'), 'geschlecht', [
            'MALE' => _('Männlich'),
            'FEMALE' => _('Weiblich'),
        ]);
        $kategorieFilter = new ListChoiceFilter(_('Kategorie'), 'kategorie', $this->getDistinctValues('kategorie'));
        $altersklasseFilter = new ListChoiceFilter(_('Altersklasse'), 'altersklasse', $this->getDistinctValues('altersklasse'));
        $searchFilter = new ListSearchFilter(_('Suche'), ['disziplin', 'kategorie', 'altersklasse']);

        $listHandler->addListFilter($jahrFilter);
        $listHandler->addListFilter($geschlechtFilter);
        $listHandler->addListFilter($kategorieFilter);
        $listHandler->addListFilter($altersklasseFilter);
        $listHandler->addListFilter($searchFilter);
    }

    // ------------------------------------
    // DETAILANSICHT
    // ------------------------------------
    public function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('disziplin', null, ['label' => _('Disziplin')])
            ->add('kategorie', null, ['label' => _('Kategorie')])
            ->add('jahr', null, ['label' => _('Jahr')])
            ->add('altersklasse', null, ['label' => _('Altersklasse')])
            ->add('geschlecht', null, ['label' => _('Geschlecht')])
            ->add('bronze', null, ['label' => _('Bronze')])
            ->add('silber', null, ['label' => _('Silber')])
            ->add('gold', null, ['label' => _('Gold')])
            ->add('einheit', null, ['label' => _('Einheit')])
            ->add('schwimmnachweis', 'boolean', [
                'label' => _('Schwimmnachweis'),
                'choices' => [true => _('Ja'), false => _('Nein')],
            ])
            ->add('berechnungsart', null, ['label' => _('Berechnungsart')]);
    }

    // ------------------------------------
    // FORMULAR
    // ------------------------------------
    public function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
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
            ->add('schwimmnachweis', 'boolean', ['label' => _('Schwimmnachweis')])
            ->add('berechnungsart', null, ['label' => _('Berechnungsart')]);
    }

    // ------------------------------------
    // DISTINCT-WERTE AUS DER DB
    // ------------------------------------
    private function getDistinctValues(string $field): array
    {
        $repo = $this->objectManager->getRepository(SportabzeichenRequirement::class);
        $qb = $repo->createQueryBuilder('e')
            ->select("DISTINCT e.$field")
            ->orderBy("e.$field", 'ASC');

        $results = $qb->getQuery()->getScalarResult();

        $values = [];
        foreach ($results as $row) {
            $val = $row[$field] ?? null;
            if ($val !== null && $val !== '') {
                $values[$val] = (string)$val;
            }
        }
        return $values;
    }

    // ------------------------------------
    // AKTIONEN (Detailseite)
    // ------------------------------------
    public function getShowActions(CrudInterface $item): OrderedCollection
    {
        $actions = parent::getShowActions($item);

        $actions->add(
            Link::create(
                $this->router()->generate('sportabzeichen_export', ['id' => $item->getId()]),
                _('Exportieren'),
                'download',
                'btn-info'
            )
        );

        return $actions;
    }
}
