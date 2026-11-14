<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalQuestionnaire extends Model
{

    use HasFactory;
    protected $fillable = [
        'patient_id',
        'answers',
        'conditions',
        'agreed',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'conditions' => 'array',
            'agreed' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** The women-only section applies to female patients. */
    public static function showsWomenSection(Patient $patient): bool
    {
        $gender = $patient->gender;
        $gender = $gender instanceof \BackedEnum ? $gender->value : $gender;

        return strtolower((string) $gender) === 'female';
    }

    /** The questions from config, shaped as [{key, label}] lists for the frontend. */
    public static function sections(): array
    {
        $format = fn (array $items) => collect($items)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();

        return [
            'general' => $format(config('medical_questionnaire.general')),
            'women' => $format(config('medical_questionnaire.women')),
            'conditions' => $format(config('medical_questionnaire.conditions')),
        ];
    }
}