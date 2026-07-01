<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
