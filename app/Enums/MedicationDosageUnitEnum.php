<?php

namespace App\Enums;

use App\Models\Medication;

enum MedicationFrequencyEnum: string
{

    // this is for Medication 
    case Mg = 'mg';
    case Ml = 'ml';
    case Tablet = 'tablet';
    case Capsule = 'capsule';
    case Drop = 'drop';
}
