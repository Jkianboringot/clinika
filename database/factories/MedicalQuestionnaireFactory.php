<?php

namespace Database\Factories;

use App\Models\MedicalQuestionnaire;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalQuestionnaire>
 */
class MedicalQuestionnaireFactory extends Factory
{
    protected $model = MedicalQuestionnaire::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'answers' => $this->answersFor(array_keys(config('medical_questionnaire.general', []))),
            'conditions' => $this->faker->randomElements(
                array_keys(config('medical_questionnaire.conditions', [])),
                $this->faker->numberBetween(0, 3),
            ),
            'agreed' => true,
        ];
    }

    /** Every question answered No, no conditions (same as the staff "skip"). */
    public function allNo(): static
    {
        return $this->state(function (array $attributes) {
            $keys = array_keys(config('medical_questionnaire.general', []));

            return [
                'answers' => array_fill_keys($keys, false),
                'conditions' => [],
                'agreed' => false,
            ];
        });
    }

    /** Includes the women-only answers (use with a female patient). */
    public function withWomenSection(): static
    {
        return $this->state(function (array $attributes) {
            $keys = array_merge(
                array_keys(config('medical_questionnaire.general', [])),
                array_keys(config('medical_questionnaire.women', [])),
            );

            return ['answers' => $this->answersFor($keys)];
        });
    }

    /** Random true/false answers, mostly No. */
    private function answersFor(array $keys): array
    {
        return collect($keys)
            ->mapWithKeys(fn ($key) => [$key => $this->faker->boolean(20)])
            ->all();
    }
}