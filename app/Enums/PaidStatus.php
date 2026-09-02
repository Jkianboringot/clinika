<?php

namespace App\Enums;

enum PaidStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Advance_Paid = 'advance_paid';
}
