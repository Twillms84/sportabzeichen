<?php
// modules/PulsR/SportabzeichenBundle/PulsRSportabzeichenBundle.php

namespace PulsR\SportabzeichenBundle;

use PulsR\SportabzeichenBundle\DependencyInjection\PulsRSportabzeichenExtension;
use IServ\CoreBundle\Routing\AutoloadRoutingBundleInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PulsRSportabzeichenBundle extends Bundle implements AutoloadRoutingBundleInterface
{
    public function getContainerExtension()
    {
        return new PulsRSportabzeichenExtension();
    }

    public function getPrivileges(): array
    {
        return [
            'PRIV_SPORTABZEICHEN_REQUIREMENTS' => [
                'name' => 'Sportabzeichen – Anforderungen verwalten',
                'description' => 'Importieren und Bearbeiten der Sportabzeichen-Anforderungen.',
            ],
            'PRIV_SPORTABZEICHEN_MANAGE_PARTICIPANTS' => [
                'name' => 'Sportabzeichen – Teilnehmer verwalten',
                'description' => 'Teilnehmer zu Prüfungen hinzufügen oder entfernen.',
            ],
            'PRIV_SPORTABZEICHEN_RESULTS' => [
                'name' => 'Sportabzeichen – Ergebnisse eintragen',
                'description' => 'Erfassungsmaske für Listen- und Einzel-Ergebniseingabe.',
            ],
        ];
}

}
