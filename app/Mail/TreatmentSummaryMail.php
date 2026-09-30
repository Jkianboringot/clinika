<?php

namespace App\Mail;

use App\Models\Medication;
use App\Models\Treatment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TreatmentSummaryMail extends Mailable
{
    use SerializesModels;

    public $patient;
    public $prescriptions;

    public function __construct(public Treatment $treatment)
    {
        $treatment->loadMissing(['appointment.patient', 'perscriptions']);

        $this->patient = $treatment->appointment->patient;

        // resolve medication names (same fallback chain you use in the controller)
        $meds = Medication::whereIn('id', $treatment->perscriptions->pluck('medication_id'))
            ->get()
            ->keyBy('id');

        $this->prescriptions = $treatment->perscriptions->map(function ($p) use ($meds) {
            $m = $meds->get($p->medication_id);
            return [
                'name' => $m?->name ?? $m?->medication_name ?? $m?->generic_name ?? "Medication #{$p->medication_id}",
                'dosage' => $p->dosage,
                'frequency' => $p->frequency,
                'duration_days' => $p->duration_days,
            ];
        });
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Treatment Summary');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.treatment-summary');
    }
}