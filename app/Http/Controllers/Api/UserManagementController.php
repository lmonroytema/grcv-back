<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['role', 'colaborador']);

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->string('email')->trim().'%');
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', (int) $request->input('role_id'));
        }

        if ($request->filled('visible_name')) {
            $query->whereHas('colaborador', function ($builder) use ($request): void {
                $builder->where('apellidos_y_nombres', 'like', '%'.$request->string('visible_name')->trim().'%');
            });
        }

        return response()->json(
            $query->orderByDesc('id')->get()->map(fn (User $user) => $this->transformUser($user))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        $user = User::query()->create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
        ]);

        return response()->json($this->transformUser($user->load(['role', 'colaborador'])), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);

        $payload = [
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return response()->json($this->transformUser($user->fresh()->load(['role', 'colaborador'])));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json(Role::query()->orderBy('id')->get());
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role_name' => $user->role_name,
            'visible_name' => $user->visible_name,
        ];
    }
}
