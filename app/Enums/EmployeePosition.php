<?php

namespace App\Enums;

enum EmployeePosition: string
{
    case Cashier = 'cashier';
    case Head_Staff = 'head_staff';
    case Delivery_Staff = 'delivery_staff';
    case Baker = 'baker';
}
