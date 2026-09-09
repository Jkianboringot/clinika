<?php

namespace App\Enums;

/**
 * NOTE: mirrors IsActiveEnum's shape (string-backed enum) so both fields
 * validate/cast the same way. Values match the DATA FIELDS section of
 * UserController's docblock: admin | dentist | staff | patient.
 */
enum RoleEnum: string
{
    case ADMIN = 'admin';
    case DENTIST = 'dentist';
    case STAFF = 'staff';
    case PATIENT = 'patient';

    /**
     * Human label for <select> options on the frontend.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::DENTIST => 'Dentist',
            self::STAFF => 'Staff',
            self::PATIENT => 'Patient',
        };
    }
}
