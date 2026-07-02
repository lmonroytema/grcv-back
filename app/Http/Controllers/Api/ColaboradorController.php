<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColaboradorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dni = trim((string) $request->query('dni', ''));

        if ($dni !== '') {
            $colaborador = Colaborador::query()->where('n_documento', $dni)->first();

            if (!$colaborador) {
                return response()->json([
                    'message' => 'No se encontró el colaborador.',
                ], 404);
            }

            return response()->json($colaborador);
        }

        return response()->json(
            Colaborador::query()->orderBy('apellidos_y_nombres')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'apellidos_y_nombres' => ['required', 'string', 'max:255'],
            'n_documento' => ['required', 'string', 'max:20', 'unique:colaboradores,n_documento'],
            'fecha_ingreso' => ['required', 'date'],
            'area' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'aprobador_1' => ['nullable', 'string', 'max:255'],
            'aprobador_2' => ['nullable', 'string', 'max:255'],
        ]);

        $colaborador = Colaborador::query()->create($data);

        return response()->json($colaborador, 201);
    }

    public function update(Request $request, string $documento): JsonResponse
    {
        $colaborador = Colaborador::query()->where('n_documento', $documento)->firstOrFail();

        $data = $request->validate([
            'apellidos_y_nombres' => ['required', 'string', 'max:255'],
            'fecha_ingreso' => ['required', 'date'],
            'area' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'aprobador_1' => ['nullable', 'string', 'max:255'],
            'aprobador_2' => ['nullable', 'string', 'max:255'],
        ]);

        $colaborador->update($data);

        return response()->json($colaborador->fresh());
    }

    public function destroy(string $documento): JsonResponse
    {
        $colaborador = Colaborador::query()->where('n_documento', $documento)->firstOrFail();
        $colaborador->delete();

        return response()->json([
            'message' => 'Colaborador eliminado correctamente.',
        ]);
    }
}
