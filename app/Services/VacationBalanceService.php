<?php

namespace App\Services;

use App\Models\Colaborador;
use App\Models\User;
use App\Models\VacationBalance;
use App\Models\VacationBalanceMovement;
use App\Models\VacationRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacationBalanceService
{
    public function ensureBalanceForColaborador(Colaborador $colaborador): VacationBalance
    {
        return VacationBalance::query()->firstOrCreate(
            ['colaborador_id' => $colaborador->id],
            [
                'accrued_days' => 0,
                'reserved_days' => 0,
                'taken_days' => 0,
            ]
        );
    }

    public function syncApprovalStatus(VacationRequest $vacation, int $previousStatus, int $newStatus, ?User $actor = null): ?VacationBalance
    {
        $colaborador = Colaborador::query()->where('n_documento', $vacation->dni)->first();

        if (!$colaborador) {
            return null;
        }

        $balance = $this->ensureBalanceForColaborador($colaborador);

        DB::transaction(function () use ($vacation, $previousStatus, $newStatus, $actor, $balance, $colaborador): void {
            $days = (float) $vacation->days;

            if ($previousStatus !== 2 && $newStatus === 2) {
                $balance->increment('reserved_days', $days);

                VacationBalanceMovement::query()->create([
                    'colaborador_id' => $colaborador->id,
                    'vacation_request_id' => $vacation->id,
                    'user_id' => $actor?->id,
                    'type' => 'approval',
                    'days' => $days,
                    'effective_date' => now()->toDateString(),
                    'notes' => 'Aprobacion de solicitud de vacaciones.',
                    'metadata' => [
                        'vacation_request_id' => $vacation->id,
                    ],
                ]);

                return;
            }

            if ($previousStatus === 2 && $newStatus !== 2) {
                $currentReserved = (float) $balance->reserved_days;
                $nextReserved = max(0, $currentReserved - $days);
                $balance->update(['reserved_days' => $nextReserved]);

                VacationBalanceMovement::query()->create([
                    'colaborador_id' => $colaborador->id,
                    'vacation_request_id' => $vacation->id,
                    'user_id' => $actor?->id,
                    'type' => 'approval_reversal',
                    'days' => -1 * $days,
                    'effective_date' => now()->toDateString(),
                    'notes' => 'Reversion de aprobacion de solicitud.',
                    'metadata' => [
                        'vacation_request_id' => $vacation->id,
                    ],
                ]);
            }
        });

        return $balance->fresh();
    }

    public function registerTakenDays(Colaborador $colaborador, float $days, ?User $actor = null, ?string $notes = null, ?VacationRequest $vacation = null): VacationBalance
    {
        $balance = $this->ensureBalanceForColaborador($colaborador);

        if ($days <= 0) {
            throw ValidationException::withMessages([
                'days' => ['Los dias tomados deben ser mayores a cero.'],
            ]);
        }

        if ((float) $balance->reserved_days < $days) {
            throw ValidationException::withMessages([
                'days' => ['No puedes registrar mas dias tomados que dias autorizados pendientes.'],
            ]);
        }

        DB::transaction(function () use ($balance, $colaborador, $days, $actor, $notes, $vacation): void {
            $balance->update([
                'reserved_days' => max(0, (float) $balance->reserved_days - $days),
                'taken_days' => (float) $balance->taken_days + $days,
            ]);

            VacationBalanceMovement::query()->create([
                'colaborador_id' => $colaborador->id,
                'vacation_request_id' => $vacation?->id,
                'user_id' => $actor?->id,
                'type' => 'taken',
                'days' => $days,
                'effective_date' => now()->toDateString(),
                'notes' => $notes ?: 'Registro manual de vacaciones tomadas.',
            ]);
        });

        return $balance->fresh();
    }

    public function applyMonthlyAccrual(array $colaboradorIds, string $appliedMonth, float $days = 2.5, ?User $actor = null): array
    {
        $processed = [];
        $skipped = [];

        $colaboradores = Colaborador::query()
            ->whereIn('id', $colaboradorIds)
            ->orderBy('apellidos_y_nombres')
            ->get();

        foreach ($colaboradores as $colaborador) {
            $alreadyApplied = VacationBalanceMovement::query()
                ->where('colaborador_id', $colaborador->id)
                ->where('type', 'monthly_accrual')
                ->where('applied_month', $appliedMonth)
                ->exists();

            if ($alreadyApplied) {
                $skipped[] = [
                    'colaborador_id' => $colaborador->id,
                    'name' => $colaborador->apellidos_y_nombres,
                    'reason' => 'El devengo del mes ya fue aplicado.',
                ];
                continue;
            }

            $balance = $this->ensureBalanceForColaborador($colaborador);

            DB::transaction(function () use ($balance, $colaborador, $days, $actor, $appliedMonth): void {
                $balance->increment('accrued_days', $days);

                VacationBalanceMovement::query()->create([
                    'colaborador_id' => $colaborador->id,
                    'user_id' => $actor?->id,
                    'type' => 'monthly_accrual',
                    'days' => $days,
                    'effective_date' => now()->toDateString(),
                    'applied_month' => $appliedMonth,
                    'notes' => 'Devengo manual mensual de vacaciones.',
                ]);
            });

            $processed[] = [
                'colaborador_id' => $colaborador->id,
                'name' => $colaborador->apellidos_y_nombres,
                'days' => $days,
            ];
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }

    public function buildBalanceListing(?string $search = null): Collection
    {
        $query = Colaborador::query()
            ->with('vacationBalance')
            ->orderBy('apellidos_y_nombres');

        $term = trim((string) $search);
        if ($term !== '') {
            $query->where(function ($builder) use ($term): void {
                $like = '%'.$term.'%';
                $builder->where('apellidos_y_nombres', 'like', $like)
                    ->orWhere('n_documento', 'like', $like)
                    ->orWhere('correo', 'like', $like)
                    ->orWhere('area', 'like', $like);
            });
        }

        return $query->get()->map(function (Colaborador $colaborador): array {
            $balance = $colaborador->vacationBalance ?: $this->ensureBalanceForColaborador($colaborador);
            $accrued = (float) $balance->accrued_days;
            $reserved = (float) $balance->reserved_days;
            $taken = (float) $balance->taken_days;

            return [
                'colaborador_id' => $colaborador->id,
                'name' => $colaborador->apellidos_y_nombres,
                'dni' => $colaborador->n_documento,
                'area' => $colaborador->area,
                'email' => $colaborador->correo,
                'accrued_days' => round($accrued, 2),
                'reserved_days' => round($reserved, 2),
                'taken_days' => round($taken, 2),
                'available_days' => round($accrued - $reserved - $taken, 2),
            ];
        });
    }
}
