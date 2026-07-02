<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncRoles();

        if (Schema::hasTable('vacation_requests')) {
            Schema::table('vacation_requests', function (Blueprint $table): void {
                if (!Schema::hasColumn('vacation_requests', 'approved_by_user_id')) {
                    $table->foreignId('approved_by_user_id')->nullable()->after('estado')->constrained('users');
                }

                if (!Schema::hasColumn('vacation_requests', 'approved_at')) {
                    $table->dateTime('approved_at')->nullable()->after('approved_by_user_id');
                }
            });
        }

        if (!Schema::hasTable('vacation_balances')) {
            Schema::create('vacation_balances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('colaborador_id')->unique()->constrained('colaboradores')->cascadeOnDelete();
                $table->decimal('accrued_days', 10, 2)->default(0);
                $table->decimal('reserved_days', 10, 2)->default(0);
                $table->decimal('taken_days', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vacation_balance_movements')) {
            Schema::create('vacation_balance_movements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
                $table->foreignId('vacation_request_id')->nullable()->constrained('vacation_requests')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 50);
                $table->decimal('days', 10, 2);
                $table->date('effective_date');
                $table->string('applied_month', 7)->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['colaborador_id', 'type']);
                $table->index(['applied_month', 'type']);
            });
        }

        $this->seedBalances();
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_balance_movements');
        Schema::dropIfExists('vacation_balances');

        if (Schema::hasTable('vacation_requests')) {
            Schema::table('vacation_requests', function (Blueprint $table): void {
                if (Schema::hasColumn('vacation_requests', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }

                if (Schema::hasColumn('vacation_requests', 'approved_by_user_id')) {
                    $table->dropConstrainedForeignId('approved_by_user_id');
                }
            });
        }
    }

    private function syncRoles(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $trabajadorId = DB::table('roles')->where('name', 'Trabajador')->value('id');
        $empleadoId = DB::table('roles')->where('name', 'Empleado')->value('id');

        if ($trabajadorId !== null && $empleadoId === null) {
            DB::table('roles')->where('id', $trabajadorId)->update(['name' => 'Empleado']);
        } elseif ($trabajadorId !== null && $empleadoId !== null) {
            DB::table('users')->where('role_id', $trabajadorId)->update(['role_id' => $empleadoId]);
            DB::table('roles')->where('id', $trabajadorId)->delete();
        }

        foreach (['Administrador', 'Supervisor', 'Empleado', 'Visitante'] as $roleName) {
            if (!DB::table('roles')->where('name', $roleName)->exists()) {
                DB::table('roles')->insert(['name' => $roleName]);
            }
        }
    }

    private function seedBalances(): void
    {
        if (!Schema::hasTable('colaboradores') || !Schema::hasTable('vacation_balances')) {
            return;
        }

        $colaboradores = DB::table('colaboradores')
            ->select('id', 'n_documento')
            ->orderBy('id')
            ->get();

        foreach ($colaboradores as $colaborador) {
            $approvedDays = Schema::hasTable('vacation_requests')
                ? (float) DB::table('vacation_requests')
                    ->where('dni', $colaborador->n_documento)
                    ->where('estado', 2)
                    ->sum('days')
                : 0.0;

            DB::table('vacation_balances')->updateOrInsert(
                ['colaborador_id' => $colaborador->id],
                [
                    'accrued_days' => 0,
                    'reserved_days' => $approvedDays,
                    'taken_days' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
};
