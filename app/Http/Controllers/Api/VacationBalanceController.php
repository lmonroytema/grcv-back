<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\VacationRequest;
use App\Services\VacationBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacationBalanceController extends Controller
{
    public function __construct(
        private readonly VacationBalanceService $balanceService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->balanceService->buildBalanceListing($request->string('search')->toString())
        );
    }

    public function applyMonthlyAccrual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'colaborador_ids' => ['required', 'array', 'min:1'],
            'colaborador_ids.*' => ['integer', 'exists:colaboradores,id'],
            'applied_month' => ['required', 'date_format:Y-m'],
            'days' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $result = $this->balanceService->applyMonthlyAccrual(
            $data['colaborador_ids'],
            $data['applied_month'],
            (float) ($data['days'] ?? 2.5),
            $request->user()
        );

        return response()->json([
            'message' => 'Devengo aplicado correctamente.',
            ...$result,
        ]);
    }

    public function registerTaken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'days' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'vacation_request_id' => ['nullable', 'integer', 'exists:vacation_requests,id'],
        ]);

        $colaborador = Colaborador::query()->findOrFail($data['colaborador_id']);
        $vacation = !empty($data['vacation_request_id'])
            ? VacationRequest::query()->find($data['vacation_request_id'])
            : null;

        $balance = $this->balanceService->registerTakenDays(
            $colaborador,
            (float) $data['days'],
            $request->user(),
            $data['notes'] ?? null,
            $vacation
        );

        return response()->json([
            'message' => 'Vacaciones tomadas registradas correctamente.',
            'balance' => [
                'colaborador_id' => $colaborador->id,
                'accrued_days' => round((float) $balance->accrued_days, 2),
                'reserved_days' => round((float) $balance->reserved_days, 2),
                'taken_days' => round((float) $balance->taken_days, 2),
                'available_days' => round((float) $balance->accrued_days - (float) $balance->reserved_days - (float) $balance->taken_days, 2),
            ],
        ]);
    }
}
