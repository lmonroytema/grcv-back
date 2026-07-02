<?php

namespace Tests\Feature;

use App\Mail\VacationApprovedMail;
use App\Models\Colaborador;
use App\Models\Role;
use App\Models\User;
use App\Models\VacationBalance;
use App\Models\VacationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VacationApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_view_and_approve_only_subordinate_requests(): void
    {
        Mail::fake();
        config()->set('vacations.notifications.approval_emails', ['control@tema.com.pe']);

        $supervisorRole = Role::query()->create(['name' => 'Supervisor']);
        $employeeRole = Role::query()->create(['name' => 'Empleado']);

        $supervisor = User::query()->create([
            'email' => 'supervisor@tema.com.pe',
            'password' => 'Secret123*',
            'role_id' => $supervisorRole->id,
        ]);

        Colaborador::query()->create([
            'apellidos_y_nombres' => 'Supervisor Uno',
            'n_documento' => '70000001',
            'fecha_ingreso' => '2024-01-01',
            'area' => 'Operaciones',
            'correo' => 'supervisor@tema.com.pe',
        ]);

        User::query()->create([
            'email' => 'empleado@tema.com.pe',
            'password' => 'Secret123*',
            'role_id' => $employeeRole->id,
        ]);

        $subordinateColaborador = Colaborador::query()->create([
            'apellidos_y_nombres' => 'Empleado Subordinado',
            'n_documento' => '70000002',
            'fecha_ingreso' => '2024-02-01',
            'area' => 'Operaciones',
            'correo' => 'empleado@tema.com.pe',
            'aprobador_1' => 'supervisor@tema.com.pe',
        ]);

        $subordinateVacation = VacationRequest::query()->create([
            'start_time' => now()->format('Y-m-d 08:00:00'),
            'end_time' => now()->addDays(7)->format('Y-m-d 18:00:00'),
            'email' => 'empleado@tema.com.pe',
            'last_name' => 'Empleado',
            'mother_name' => 'Subordinado',
            'first_name' => 'Juan',
            'second_name' => '',
            'dni' => '70000002',
            'area' => 'Operaciones',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(8)->format('Y-m-d'),
            'days' => 8,
            'confirmation_image' => 'test.pdf',
            'pdf_file' => 'test.pdf',
            'estado' => 1,
        ]);

        VacationRequest::query()->create([
            'start_time' => now()->format('Y-m-d 08:00:00'),
            'end_time' => now()->addDays(4)->format('Y-m-d 18:00:00'),
            'email' => 'otro@tema.com.pe',
            'last_name' => 'Otro',
            'mother_name' => 'Trabajador',
            'first_name' => 'Luis',
            'second_name' => '',
            'dni' => '70000099',
            'area' => 'Logistica',
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'end_date' => now()->addDays(6)->format('Y-m-d'),
            'days' => 5,
            'confirmation_image' => 'otro.pdf',
            'pdf_file' => 'otro.pdf',
            'estado' => 1,
        ]);

        Sanctum::actingAs($supervisor);

        $listResponse = $this->getJson('/api/vacations');
        $listResponse->assertOk();
        $this->assertCount(1, $listResponse->json('data'));
        $this->assertSame($subordinateVacation->id, $listResponse->json('data.0.id'));

        $approveResponse = $this->patchJson('/api/vacations/'.$subordinateVacation->id.'/status', [
            'estado' => 2,
        ]);

        $approveResponse->assertOk();
        $approveResponse->assertJsonPath('estado', 2);
        $approveResponse->assertJsonPath('notifications.employee.sent', true);
        $approveResponse->assertJsonPath('notifications.approver.sent', true);

        $this->assertDatabaseHas('vacation_requests', [
            'id' => $subordinateVacation->id,
            'estado' => 2,
            'approved_by_user_id' => $supervisor->id,
        ]);

        $balance = VacationBalance::query()->where('colaborador_id', $subordinateColaborador->id)->first();
        $this->assertNotNull($balance);
        $this->assertSame('8.00', number_format((float) $balance->reserved_days, 2, '.', ''));

        Mail::assertSent(VacationApprovedMail::class, 3);
    }
}
