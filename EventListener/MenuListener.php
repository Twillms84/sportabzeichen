<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\EventListener;

use IServ\CoreBundle\Event\MenuEvent;
use IServ\CoreBundle\EventListener\MainMenuListenerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class MenuListener implements MainMenuListenerInterface
{
    private AuthorizationCheckerInterface $auth;

    public function __construct(AuthorizationCheckerInterface $auth)
    {
        $this->auth = $auth;
    }

    public function onBuildMainMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();

        /* ----------------------------------------------------------
         * Sportabzeichen – Ergebnisse
         * ---------------------------------------------------------- */
        if ($this->auth->isGranted('PRIV_SPORTABZEICHEN_RESULTS')) {
            $menu->addChild('sportabzeichen_results', [
                'route' => 'sportabzeichen_results_exams',
                'label' => _('Sportabzeichen'),
                'extras' => [
                    'icon' => 'medal',
                    'icon_style' => 'fas',
                ],
            ]);
        }

        /* ----------------------------------------------------------
         * Sportabzeichen – Verwaltung
         * ---------------------------------------------------------- */
        if ($this->auth->isGranted('PRIV_SPORTABZEICHEN_ADMIN')) {
            $menu->addChild('sportabzeichen_admin', [
                'route' => 'sportabzeichen_admin_dashboard',
                'label' => _('Sportabzeichen-Verwaltung'),
                'extras' => [
                    'icon' => 'cog',
                    'icon_style' => 'fas',
                ],
            ]);
        }
    }
}
