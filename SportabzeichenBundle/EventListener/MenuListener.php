<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\EventListener;

use IServ\CoreBundle\Event\MenuEvent;
use IServ\CoreBundle\EventListener\MainMenuListenerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Fügt das Modul "Sportabzeichen" ins Hauptmenü ein.
 */
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

        // Hauptpunkt: Sichtbar für Benutzer mit Anzeige-Recht
        if ($this->auth->isGranted('PRIV_SPORTABZEICHEN_VIEW')) {
            $root = $menu->addChild('sportabzeichen', [
                'route' => 'sportabzeichen_index',
                'label' => _('Sportabzeichen'),
                'extras' => [
                    'icon' => 'medal',
                    'icon_style' => 'fas',
                ],
            ]);

            // Unterpunkt: Verwaltung & Upload nur für Manager
            if ($this->auth->isGranted('PRIV_SPORTABZEICHEN_MANAGE')) {
                $verwaltung = $root->addChild('sportabzeichen_manage', [
                    'route' => 'sportabzeichen_manage',
                    'label' => _('Verwaltung'),
                    'extras' => [
                        'icon' => 'cog',
                        'icon_style' => 'fas',
                    ],
                ]);

                $verwaltung->addChild('sportabzeichen_upload', [
                    'route' => 'sportabzeichen_admin_upload',
                    'label' => _('Anforderungsdatei hochladen'),
                    'extras' => [
                        'icon' => 'file-upload',
                        'icon_style' => 'fas',
                    ],
                ]);
            }
        }
    }
}
