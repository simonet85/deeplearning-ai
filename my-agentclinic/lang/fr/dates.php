<?php

/*
| Styles de date et d'heure, utilisés avec $date->localized('short') (voir AppServiceProvider). Les lettres sont
| celles de date() en PHP ; les noms des jours et des mois suivent la langue courante. L'heure reste sur 24 heures.
*/

return [
    'short' => 'D j M',
    'datetime' => 'D j M · H:i',
    'stamp' => 'j M, H:i',
    'day' => 'j M',
    'long' => 'l j F Y',
];
