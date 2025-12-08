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
        if ($this->auth->isGranted('PRIV_SPORTABZEICHEN_VIEW') || $this->auth->isGranted('PRIV_SPORTABZEICHEN_MANAGE')) {
            $root = $menu->addChild('sportabzeichen', [
                'route' => 'sportabzeichen_index',
                'label' => _('Sportabzeichen'),
                'extras' => [
                    'icon' => 'medal',
                    'icon_style' => 'fas',
                ],
            ]);

            // Unterpunkt: Verwaltung nur für Manager
            if ($this->auth->isGranted('PRIV_SPORTABZEICHEN_MANAGE')) {
                $verwaltung = $root->addChild('sportabzeichen_manage', [
                    'route' => 'sportabzeichen_manage',
                    'label' => _('Verwaltung'),
                    'extras' => [
                        'icon' => 'cog',
                        'icon_style' => 'fas',
                    ],
                ]);

                // ➕ Unterpunkt 1: Übersicht
                $verwaltung->addChild('sportabzeichen_manage_overview', [
                    'route' => 'sportabzeichen_manage',
                    'label' => _('Übersicht'),
                    'extras' => [
                        'icon' => 'list',
                        'icon_style' => 'fas',
                    ],
                ]);

                // ➕ Unterpunkt 2: Disziplinanforderungen anzeigen
                $verwaltung->addChild('sportabzeichen_manage_view', [
                    'route' => 'sportabzeichen_manage_view',
                    'label' => _('Disziplinanforderungen anzeigen'),
                    'extras' => [
                        'icon' => 'table',
                        'icon_style' => 'fas',
                    ],
                ]);

                // ➕ Unterpunkt 3: CSV-Upload
                $verwaltung->addChild('sportabzeichen_upload', [
                    'route' => 'sportabzeichen_admin_upload',
                    'label' => _('Anforderungsdatei hochladen'),
                    'extras' => [
                        'icon' => 'file-upload',
                        'icon_style' => 'fas',
                    ],
                ]);
                $verwaltung->addChild('sportabzeichen_crud', [
                    'route' => 'iserv_crud_sportabzeichenrequirement_index',
                    'label' => _('Anforderungen bearbeiten'),
                    'extras' => [
                       'icon' => 'edit',
                       'icon_style' => 'fas',
                    ],
                ]);
            }
        }
    }
}
