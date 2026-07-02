<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VacationBalance extends Model
{
    use HasFactory;

    protected $table = 'vacation_balances';

    protected $fillable = [
        'colaborador_id',
        'accrued_days',
        'reserved_days',
        'taken_days',
    ];

    protected function casts(): array
    {
        return [
            'accrued_days' => 'decimal:2',
            'reserved_days' => 'decimal:2',
            'taken_days' => 'decimal:2',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(VacationBalanceMovement::class, 'colaborador_id', 'colaborador_id');
    }
}
