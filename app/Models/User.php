<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'visible_name',
        'role_name',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function colaborador(): HasOne
    {
        return $this->hasOne(Colaborador::class, 'correo', 'email');
    }

    public function getVisibleNameAttribute(): string
    {
        $name = $this->relationLoaded('colaborador')
            ? $this->colaborador?->apellidos_y_nombres
            : $this->colaborador()->value('apellidos_y_nombres');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return explode('@', $this->email)[0] ?? $this->email;
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->relationLoaded('role')
            ? $this->role?->name
            : $this->role()->value('name');
    }
}
