<?php

namespace App\Enums;

enum Shift: string
{
    case Opening = 'opening';
    case Mid = 'mid';
    case Closing = 'closing';
    case Graveyard = 'graveyard';
}
