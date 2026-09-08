<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default hours cycle
    |--------------------------------------------------------------------------
    |
    | 12 or 24. `null` reads it off each field's display format, so a field
    | showing `g:i A` gets a 12-hour dial with AM/PM and one showing `H:i`
    | gets the 24-hour dial with its inner ring. Set a number here to make
    | every picker in the app agree regardless of how it is displayed.
    |
    */

    'hours_cycle' => null,

    /*
    |--------------------------------------------------------------------------
    | Close once the last dial is set
    |--------------------------------------------------------------------------
    |
    | With this on, picking a minute (or a second, where seconds are on)
    | commits and closes the panel, and the footer keeps only Cancel. Off,
    | the panel waits to be confirmed.
    |
    */

    'close_on_complete' => false,

    /*
    |--------------------------------------------------------------------------
    | "Now" shortcut
    |--------------------------------------------------------------------------
    |
    | Shows a button in the panel footer that sets the current time. It obeys
    | the field's own minute step and its min/max bounds.
    |
    */

    'now_button' => true,

];
