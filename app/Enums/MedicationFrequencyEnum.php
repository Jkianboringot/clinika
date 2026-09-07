<?php

namespace App\Enums;

enum MedicationFrequencyEnum: string
{
    case OnceDaily = 'once_daily';
    case TwiceDaily = 'twice_daily';
    case ThreeTimesDaily = 'three_times_daily';
    case Weekly = 'weekly';
    case AsNeeded = 'as_needed';
}
