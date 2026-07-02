<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationBalanceMovement extends Model
{
    use HasFactory;

    protected $table = 'vacation_balance_movements';

    protected $fillable = [
        'colaborador_id',
        'vacation_request_id',
        'user_id',
        'type',
        'days',
        'effective_date',
        'applied_month',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'decimal:2',
            'effective_date' => 'date:Y-m-d',
            'metadata' => 'array',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }

    public function vacationRequest(): BelongsTo
    {
        return $this->belongsTo(VacationRequest::class, 'vacation_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
