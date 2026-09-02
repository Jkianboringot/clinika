<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Branch = 'branch';
    case Central = 'central';
    case Commissary = 'commissary';
}
