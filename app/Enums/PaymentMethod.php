<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Digital_Cash = 'digital_cash';
    case Bank = 'bank';
    case Hybrid = 'hybrid';
}
