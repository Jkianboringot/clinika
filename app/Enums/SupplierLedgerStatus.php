<?php

namespace App\Enums;

enum SupplierLedgerStatus: string
{
    case Active = 'active';
    case Paid = 'paid';
    case Overdue = 'overdue';
}
