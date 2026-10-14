<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\IsActiveEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $appends = ['avatar_url'];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => IsActiveEnum::class
        ];
    }
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'branch_id',
        'is_active'
    ];

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn() => $this->avatar
            ? asset('storage/' . $this->avatar)
            : null);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }
    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'staff_id');
    }

    public function treatment()
    {
        return $this->hasMany(Treatment::class, 'user_id');
    }




}
