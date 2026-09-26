<?php

namespace App\Exceptions;

class TreatmentLimitExceededException extends \RuntimeException
{
    public static function limitReached(int $appointmentId): self
    {
        return new self(
            "Appointment #{$appointmentId} already has a treatment. Each appointment may only have one treatment."
        );
    }
}