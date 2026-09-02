<?php

namespace App\Enums;

enum ExpenseType: string
{
    case Salary = 'salary';
    case Rent = 'rent';
    case Utilities = 'utilities';
    case Loans = 'loans';
}
