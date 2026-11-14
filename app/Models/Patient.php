<?php

namespace App\Models;

use App\Enums\IsActiveEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Patient extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasRoles;

    public $guarded = ['id'];

  protected $hidden = ['password', 'remember_token'];

    protected $guard_name = 'patient';

    protected $appends = ['full_name'];

    protected function fullName(): Attribute
    {
        return Attribute::get(fn() => collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()->implode(' '));
    }

    /**
     * SQL-side name search. Accessors can't be used in WHERE/ORDER BY,
     * so this searches the real columns.
     */
    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q, string $v) {
            $like = "%{$v}%";

            $fullName = $this->getConnection()->getDriverName() === 'mysql'
                ? "CONCAT_WS(' ', first_name, middle_name, last_name)"
                : "(COALESCE(first_name, '') || ' ' || COALESCE(middle_name, '') || ' ' || COALESCE(last_name, ''))";

            $q->where(function (Builder $q) use ($like, $fullName) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("{$fullName} like ?", [$like]);
            });
        });
    }

    

    protected function casts(): array
    {
        return [
            'is_active' => IsActiveEnum::class,
            'password' => 'hashed',
        ];
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    protected static function booted(): void
    {
        static::created(function (Patient $patient) {
            $patient->assignRole('patient');
        });
    }

    public function medicalQuestionnaires(): HasMany
{
    return $this->hasMany(MedicalQuestionnaire::class);
}
}