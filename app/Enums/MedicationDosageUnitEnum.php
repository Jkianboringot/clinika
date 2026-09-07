<?php

namespace App\Enums;

enum MedicationFrequencyEnum: string
{
    case Mg = 'mg';
    case Ml = 'ml';
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Drop = 'drop';
}
