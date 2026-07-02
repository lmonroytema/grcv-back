<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Colaborador extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'colaboradores';

    protected $fillable = [
        'apellidos_y_nombres',
        'n_documento',
        'fecha_ingreso',
        'area',
        'correo',
        'aprobador_1',
        'aprobador_2',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date:Y-m-d',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'email', 'correo');
    }

    public function vacationBalance(): HasOne
    {
        return $this->hasOne(VacationBalance::class, 'colaborador_id');
    }

    public function balanceMovements(): HasMany
    {
        return $this->hasMany(VacationBalanceMovement::class, 'colaborador_id');
    }
}
