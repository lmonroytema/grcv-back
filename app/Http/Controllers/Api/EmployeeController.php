<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmployeeLookupService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeLookupService $employeeLookupService)
    {
    }

    public function show(): JsonResponse
    {
        $dniType = (string) request()->query('dni_type', '1');
        $dni = trim((string) request()->query('dni', ''));

        if ($dni === '') {
            return response()->json([
                'success' => false,
                'message' => 'El número de documento es obligatorio.',
            ], 422);
        }

        $result = $this->employeeLookupService->getEmployeeInfo($dniType, $dni);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function areas(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'areas' => $this->employeeLookupService->getAreas(),
        ]);
    }

    public function template()
    {
        $candidates = [
            public_path('formatos\\solicitud-vacaciones.pdf'),
            base_path('..\\public\\formatos\\solicitud-vacaciones.pdf'),
            base_path('..\\..\\public\\formatos\\solicitud-vacaciones.pdf'),
        ];

        $path = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === null) {
            abort(404, 'Plantilla no disponible.');
        }

        return response()->download($path, 'solicitud-vacaciones.pdf');
    }
}
