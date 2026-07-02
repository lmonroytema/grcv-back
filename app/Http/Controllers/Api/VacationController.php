<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\User;
use App\Models\VacationRequest;
use App\Services\VacationBalanceService;
use App\Services\VacationNotificationService;
use App\Services\VacationPdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VacationController extends Controller
{
    public function __construct(
        private readonly VacationPdfService $pdfService,
        private readonly VacationNotificationService $notificationService,
        private readonly VacationBalanceService $balanceService
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = VacationRequest::query()->orderByDesc('id');

        if ($user !== null && $this->isEmployee($user)) {
            $query->where('email', $user->email);
        } elseif ($user !== null && $user->isSupervisor()) {
            $subordinateDnis = $this->resolveSubordinateDnis($user);
            $query->where(function ($builder) use ($user, $subordinateDnis): void {
                $builder->where('email', $user->email);

                if ($subordinateDnis !== []) {
                    $builder->orWhereIn('dni', $subordinateDnis);
                }
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', (int) $request->input('estado'));
        }

        if ($request->filled('dni')) {
            $query->where('dni', 'like', '%'.$request->string('dni')->trim().'%');
        }

        if ($request->filled('email') && !$this->isEmployee($user)) {
            $query->where('email', 'like', '%'.$request->string('email')->trim().'%');
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search')->trim().'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('dni', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('mother_name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('second_name', 'like', $term)
                    ->orWhere('area', 'like', $term);
            });
        }

        $records = $query->paginate(min(max((int) $request->input('per_page', 15), 1), 100));
        $records->getCollection()->transform(fn (VacationRequest $vacation) => $this->transformVacation($vacation));

        return response()->json($records);
    }

    public function show(Request $request, VacationRequest $vacation): JsonResponse
    {
        $this->ensureCanView($request->user(), $vacation);

        return response()->json($this->transformVacation($vacation));
    }

    public function store(Request $request): JsonResponse
    {
        $role = $request->user()?->role_name;
        if (in_array($role, ['Empleado', 'Trabajador', 'Visitante'], true)) {
            return response()->json([
                'message' => 'Tu perfil es solo de consulta y no puede registrar solicitudes.',
            ], 403);
        }

        $data = $this->validatePayload($request, true);
        $this->validateBusinessRules($data);
        $this->ensureNoOverlap($data['dni'], $data['start_date'], $data['end_date']);

        $attachment = $request->file('confirmation_image');
        $attachmentFilename = now()->format('YmdHis').'-'.Str::slug(pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$attachment->getClientOriginalExtension();
        $attachment->storeAs('vacations/attachments', $attachmentFilename, 'public');

        $vacation = VacationRequest::query()->create([
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => Carbon::parse($data['end_time']),
            'email' => $data['email'],
            'last_name' => $data['last_name'],
            'mother_name' => $data['mother_name'],
            'first_name' => $data['first_name'],
            'second_name' => $data['second_name'] ?? '',
            'dni' => $data['dni'],
            'area' => $data['area'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1,
            'confirmation_image' => $attachmentFilename,
            'estado' => 1,
        ]);

        $vacation->update(['pdf_file' => $this->pdfService->generate($vacation)]);
        $vacation = $vacation->fresh();
        $notifications = $this->notificationService->sendCreatedNotifications($vacation);

        return response()->json([
            ...$this->transformVacation($vacation),
            'notifications' => $notifications,
        ], 201);
    }

    public function update(Request $request, VacationRequest $vacation): JsonResponse
    {
        $data = $this->validatePayload($request, false);
        $this->validateBusinessRules($data);
        $this->ensureNoOverlap($data['dni'], $data['start_date'], $data['end_date'], $vacation->id);

        $payload = [
            'start_time' => Carbon::parse($data['start_time']),
            'end_time' => Carbon::parse($data['end_time']),
            'email' => $data['email'],
            'last_name' => $data['last_name'],
            'mother_name' => $data['mother_name'],
            'first_name' => $data['first_name'],
            'second_name' => $data['second_name'] ?? '',
            'dni' => $data['dni'],
            'area' => $data['area'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1,
        ];

        if ($request->hasFile('confirmation_image')) {
            $attachment = $request->file('confirmation_image');
            $filename = now()->format('YmdHis').'-'.Str::slug(pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$attachment->getClientOriginalExtension();
            $attachment->storeAs('vacations/attachments', $filename, 'public');
            $payload['confirmation_image'] = $filename;
        }

        $vacation->update($payload);
        $vacation->update(['pdf_file' => $this->pdfService->generate($vacation->fresh())]);

        return response()->json($this->transformVacation($vacation->fresh()));
    }

    public function updateStatus(Request $request, VacationRequest $vacation): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(response()->json(['message' => 'No autenticado.'], 401));
        }

        $this->ensureCanApprove($user, $vacation);

        $data = $request->validate([
            'estado' => ['required', 'integer', Rule::in([0, 1, 2])],
        ]);

        $previousStatus = (int) $vacation->estado;
        $newStatus = (int) $data['estado'];

        $payload = ['estado' => $newStatus];

        if ($newStatus === 2) {
            $payload['approved_by_user_id'] = $user->id;
            $payload['approved_at'] = now();
        } elseif ($previousStatus === 2) {
            $payload['approved_by_user_id'] = null;
            $payload['approved_at'] = null;
        }

        $vacation->update($payload);
        $vacation = $vacation->fresh(['approvedBy']);
        $this->balanceService->syncApprovalStatus($vacation, $previousStatus, $newStatus, $user);

        $response = $this->transformVacation($vacation);

        if ($previousStatus !== 2 && $newStatus === 2) {
            $response['notifications'] = $this->notificationService->sendApprovalNotifications($vacation, $user);
        }

        return response()->json($response);
    }

    public function destroy(VacationRequest $vacation): JsonResponse
    {
        if ($vacation->confirmation_image !== '') {
            Storage::disk('public')->delete('vacations/attachments/'.$vacation->confirmation_image);
        }
        if (($vacation->pdf_file ?? '') !== '') {
            Storage::disk('public')->delete('vacations/pdfs/'.$vacation->pdf_file);
        }

        $vacation->delete();

        return response()->json([
            'message' => 'Solicitud eliminada correctamente.',
        ]);
    }

    public function pdf(Request $request, VacationRequest $vacation)
    {
        $path = $vacation->pdf_file ? $this->pdfService->resolvePdfPath($vacation->pdf_file) : null;
        abort_if($path === null, 404, 'PDF no disponible.');

        return response()->file($path);
    }

    public function attachment(Request $request, VacationRequest $vacation)
    {
        $path = $this->pdfService->resolveAttachmentPath($vacation->confirmation_image);
        abort_if($path === null, 404, 'Adjunto no disponible.');

        return response()->file($path);
    }

    private function validatePayload(Request $request, bool $requiresFile): array
    {
        return $request->validate([
            'email' => ['required', 'email', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'mother_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'second_name' => ['nullable', 'string', 'max:50'],
            'dni' => ['required', 'string', 'max:20'],
            'area' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date'],
            'confirmation_image' => [$requiresFile ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:8192'],
        ]);
    }

    private function validateBusinessRules(array $data): void
    {
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();

        if ($startDate->lt(now()->subDays(30)->startOfDay())) {
            abort(response()->json([
                'message' => 'La fecha inicial debe ser igual o posterior a hace 30 dias.',
            ], 422));
        }

        if ($endDate->lt($startDate)) {
            abort(response()->json([
                'message' => 'La fecha final no puede ser menor a la fecha inicial.',
            ], 422));
        }

        if (in_array((int) $endDate->format('N'), [5, 6], true)) {
            abort(response()->json([
                'message' => 'La fecha final no puede ser viernes o sabado.',
            ], 422));
        }
    }

    private function ensureNoOverlap(string $dni, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        $query = VacationRequest::query()
            ->where('dni', $dni)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            abort(response()->json([
                'message' => 'El periodo seleccionado se superpone con un registro existente.',
            ], 422));
        }
    }

    private function ensureCanView(?User $user, VacationRequest $vacation): void
    {
        if ($user === null) {
            abort(response()->json(['message' => 'No autenticado.'], 401));
        }

        if ($this->isEmployee($user) && $user->email !== $vacation->email) {
            abort(response()->json(['message' => 'No tienes permiso para ver este registro.'], 403));
        }

        if ($user->isSupervisor() && $user->email !== $vacation->email && !$this->isSubordinateVacation($user, $vacation)) {
            abort(response()->json(['message' => 'No tienes permiso para ver este registro.'], 403));
        }
    }

    private function isEmployee(?User $user): bool
    {
        return $user?->isEmployee() ?? false;
    }

    private function ensureCanApprove(User $user, VacationRequest $vacation): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isSupervisor() && $this->isSubordinateVacation($user, $vacation) && $user->email !== $vacation->email) {
            return;
        }

        abort(response()->json(['message' => 'No tienes permiso para aprobar esta solicitud.'], 403));
    }

    private function isSubordinateVacation(User $user, VacationRequest $vacation): bool
    {
        return in_array($vacation->dni, $this->resolveSubordinateDnis($user), true);
    }

    private function resolveSubordinateDnis(User $user): array
    {
        $colaborador = $user->relationLoaded('colaborador')
            ? $user->colaborador
            : $user->colaborador()->first();

        if (!$colaborador) {
            return [];
        }

        $identifiers = array_values(array_filter([
            $user->email,
            $colaborador->n_documento,
            $colaborador->apellidos_y_nombres,
        ], static fn (?string $value): bool => is_string($value) && trim($value) !== ''));

        if ($identifiers === []) {
            return [];
        }

        $normalizedIdentifiers = array_values(array_unique(array_map(
            fn (string $value): string => Str::lower(trim((string) preg_replace('/\s+/', ' ', $value))),
            $identifiers
        )));

        return Colaborador::query()
            ->where(function ($builder) use ($normalizedIdentifiers): void {
                foreach (['aprobador_1', 'aprobador_2'] as $field) {
                    foreach ($normalizedIdentifiers as $identifier) {
                        $builder->orWhereRaw('LOWER(TRIM('.$field.')) = ?', [$identifier]);
                    }
                }
            })
            ->pluck('n_documento')
            ->filter(static fn (?string $dni): bool => is_string($dni) && $dni !== '')
            ->values()
            ->all();
    }

    private function transformVacation(VacationRequest $vacation): array
    {
        return [
            'id' => $vacation->id,
            'dni' => $vacation->dni,
            'email' => $vacation->email,
            'full_name' => $vacation->full_name,
            'last_name' => $vacation->last_name,
            'mother_name' => $vacation->mother_name,
            'first_name' => $vacation->first_name,
            'second_name' => $vacation->second_name,
            'area' => $vacation->area,
            'start_date' => optional($vacation->start_date)->format('Y-m-d'),
            'end_date' => optional($vacation->end_date)->format('Y-m-d'),
            'start_time' => optional($vacation->start_time)->format('Y-m-d H:i:s'),
            'end_time' => optional($vacation->end_time)->format('Y-m-d H:i:s'),
            'days' => $vacation->days,
            'estado' => $vacation->estado,
            'estado_label' => match ($vacation->estado) {
                0 => 'Rechazado',
                2 => 'Aprobado',
                default => 'Pendiente',
            },
            'approved_at' => optional($vacation->approved_at)->format('Y-m-d H:i:s'),
            'approved_by_name' => $vacation->approvedBy?->visible_name,
            'pdf_url' => $vacation->pdf_file ? url('/api/vacations/'.$vacation->id.'/pdf') : null,
            'attachment_url' => url('/api/vacations/'.$vacation->id.'/attachment'),
        ];
    }
}
