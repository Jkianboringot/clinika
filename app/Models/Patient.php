<?php

namespace App\Models;

use App\Enums\IsActiveEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Patient extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasRoles;

    public $guarded = ['id'];

    protected $hidden = ['password'];

    protected $guard_name = 'patient';

    protected $appends = ['full_name'];

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => collect([
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
        return $query->when($term, fn (Builder $q, string $v) => $q->where(function (Builder $q) use ($v) {
            $q->where('first_name', 'like', "%{$v}%")
              ->orWhere('middle_name', 'like', "%{$v}%")
              ->orWhere('last_name', 'like', "%{$v}%")
              ->orWhereRaw("CONCAT_WS(' ', first_name, middle_name, last_name) like ?", ["%{$v}%"]);
        }));
    }

    protected function casts(): array
    {
        return [
            'is_active' => IsActiveEnum::class,
            'password'  => 'hashed',
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
}