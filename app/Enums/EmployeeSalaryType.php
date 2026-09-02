<?php

namespace App\Enums;

enum EmployeeSalaryType: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Quata = 'quota';
}
