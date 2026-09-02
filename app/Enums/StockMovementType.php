<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase='purchase';
    case Transfer='transfer';
    case Wastage='wastage';
}
