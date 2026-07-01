<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationRequest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'vacation_requests';

    protected $fillable = [
        'start_time',
        'end_time',
        'email',
        'last_name',
        'mother_name',
        'first_name',
        'second_name',
        'dni',
        'area',
        'start_date',
        'end_date',
        'days',
        'confirmation_image',
        'pdf_file',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:Y-m-d H:i:s',
            'end_time' => 'datetime:Y-m-d H:i:s',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'days' => 'integer',
            'estado' => 'integer',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name,
            $this->mother_name,
            $this->first_name,
            $this->second_name,
        ])));
    }
}
