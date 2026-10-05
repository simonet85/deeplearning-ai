<?php

/*
| Date and time styles, used as $date->localized('short') (see AppServiceProvider). The format letters are PHP's
| date() ones; the names of days and months come from the current language. Times stay on a 24-hour clock.
*/

return [
    'short' => 'D, M j',
    'datetime' => 'D, M j · H:i',
    'stamp' => 'M j, H:i',
    'day' => 'M j',
    'long' => 'l, F j, Y',
];
