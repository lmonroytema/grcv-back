<?php

namespace App\Services;

use App\Models\Colaborador;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class EmployeeLookupService
{
    public function getEmployeeInfo(string $dniType, string $dni): array
    {
        $result = [
            'success' => false,
            'source' => [],
            'data' => [
                'dni' => $dni,
                'first_name' => '',
                'second_name' => '',
                'last_name' => '',
                'mother_name' => '',
                'email' => '',
                'area' => '',
                'full_name' => '',
            ],
        ];

        $masterData = $this->getFromMasterData($dniType, $dni);
        if ($masterData['success']) {
            $result['data'] = array_merge($result['data'], $masterData['data']);
            $result['source'][] = 'master_data';
            $result['success'] = true;
        }

        $corporate = $this->getFromCorporateApi($dniType, $dni);
        if ($corporate['success']) {
            $result['data'] = $this->mergeEmployeeData($result['data'], $corporate['data']);
            $result['source'][] = 'corporate_api';
            $result['success'] = true;
        }

        if (
            $result['data']['email'] === ''
            || $result['data']['area'] === ''
            || $result['data']['first_name'] === ''
            || $result['data']['last_name'] === ''
        ) {
            $local = $this->getFromLocalColaboradores($dni);
            if ($local['success']) {
                $result['data'] = $this->mergeEmployeeData($result['data'], $local['data']);
                $result['source'][] = 'local_db';
                $result['success'] = true;
            }
        }

        $result['data']['full_name'] = trim(implode(' ', array_filter([
            $result['data']['first_name'],
            $result['data']['second_name'],
            $result['data']['last_name'],
            $result['data']['mother_name'],
        ])));

        return $result;
    }

    public function getAreas(): array
    {
        $areas = [
            'Administracion',
            'Contabilidad',
            'Gerencia General',
            'Logistica',
            'Limpieza y Mantenimiento',
            'Marketing',
            'Operaciones',
            'Recursos Humanos',
            'Seguridad',
            'Sistemas',
            'Ventas',
        ];

        $dbAreas = Colaborador::query()
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->all();

        $areas = array_values(array_unique(array_merge($areas, $dbAreas)));
        sort($areas);

        return $areas;
    }

    private function getFromCorporateApi(string $dniType, string $dni): array
    {
        $url = 'https://empleados.temalitoclean.com/api/employee.php';

        try {
            $response = Http::timeout(20)
                ->withOptions(['verify' => false])
                ->get($url, [
                    'dni_type' => $dniType,
                    'dni_number' => $dni,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'data' => ['email' => '', 'area' => '']];
            }

            $payload = $response->json();
            if (!Arr::get($payload, 'success') || !isset($payload['employee'])) {
                return ['success' => false, 'data' => ['email' => '', 'area' => '']];
            }

            return [
                'success' => true,
                'data' => [
                    'email' => (string) Arr::get($payload, 'employee.corporate_email', ''),
                    'area' => (string) Arr::get($payload, 'employee.area.name', ''),
                    ...$this->splitFullName((string) Arr::get($payload, 'employee.full_name', '')),
                ],
            ];
        } catch (\Throwable) {
            return ['success' => false, 'data' => ['email' => '', 'area' => '']];
        }
    }

    private function getFromMasterData(string $dniType, string $dni): array
    {
        $baseUrl = rtrim((string) env('MASTER_DATA_API_URL', ''), '/');
        $token = (string) env('MASTER_DATA_API_TOKEN', '');

        if ($baseUrl === '' || $token === '') {
            return ['success' => false, 'data' => []];
        }

        try {
            $response = Http::timeout(20)
                ->withToken($token)
                ->withOptions(['verify' => false])
                ->get($baseUrl.'/api/master-data', [
                    'type' => $dniType,
                    'number' => $dni,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'data' => []];
            }

            $record = $response->json('0');
            if (!is_array($record)) {
                return ['success' => false, 'data' => []];
            }

            return [
                'success' => true,
                'data' => [
                    'first_name' => (string) ($record['name1'] ?? ''),
                    'second_name' => (string) ($record['name2'] ?? ''),
                    'last_name' => (string) ($record['surname1'] ?? ''),
                    'mother_name' => (string) ($record['surname2'] ?? ''),
                ],
            ];
        } catch (\Throwable) {
            return ['success' => false, 'data' => []];
        }
    }

    private function getFromLocalColaboradores(string $dni): array
    {
        $colaborador = Colaborador::query()->where('n_documento', $dni)->first();

        if (!$colaborador) {
            return ['success' => false, 'data' => ['email' => '', 'area' => '']];
        }

        return [
            'success' => true,
            'data' => [
                'email' => (string) ($colaborador->correo ?? ''),
                'area' => (string) ($colaborador->area ?? ''),
                ...$this->splitFullName((string) ($colaborador->apellidos_y_nombres ?? '')),
            ],
        ];
    }

    private function mergeEmployeeData(array $current, array $incoming): array
    {
        foreach (['first_name', 'second_name', 'last_name', 'mother_name', 'email', 'area'] as $field) {
            if (($current[$field] ?? '') === '' && ($incoming[$field] ?? '') !== '') {
                $current[$field] = $incoming[$field];
            }
        }

        return $current;
    }

    private function splitFullName(string $fullName): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($fullName)) ?? '';
        if ($normalized === '') {
            return [
                'first_name' => '',
                'second_name' => '',
                'last_name' => '',
                'mother_name' => '',
            ];
        }

        $parts = preg_split('/\s+/', mb_strtoupper($normalized, 'UTF-8')) ?: [];
        $parts = array_values(array_filter($parts, static fn ($value): bool => $value !== ''));

        return [
            'last_name' => $parts[0] ?? '',
            'mother_name' => $parts[1] ?? '',
            'first_name' => $parts[2] ?? '',
            'second_name' => implode(' ', array_slice($parts, 3)),
        ];
    }
}
