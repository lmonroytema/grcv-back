<?php

namespace Tests\Feature;

use App\Mail\VacationApproverNotificationMail;
use App\Mail\VacationEmployeeConfirmationMail;
use App\Mail\VacationHrNotificationMail;
use App\Models\Colaborador;
use App\Models\VacationRequest;
use App\Services\VacationPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class VacationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_notifications_to_employee_hr_and_approvers_when_a_vacation_is_created(): void
    {
        Storage::fake('public');
        Mail::fake();

        config()->set('vacations.notifications.hr_emails', ['rrhh@tema.com.pe']);

        Colaborador::query()->create([
            'apellidos_y_nombres' => 'MONROY SOVERO LUIS FERNANDO',
            'n_documento' => '09739991',
            'fecha_ingreso' => '2024-01-10',
            'area' => 'Sistemas',
            'correo' => 'empleado@tema.com.pe',
            'aprobador_1' => 'Jefe Uno',
            'aprobador_2' => 'jefe2@tema.com.pe',
        ]);

        Colaborador::query()->create([
            'apellidos_y_nombres' => 'Jefe Uno',
            'n_documento' => '20000001',
            'fecha_ingreso' => '2020-01-10',
            'area' => 'Gerencia',
            'correo' => 'jefe1@tema.com.pe',
            'aprobador_1' => null,
            'aprobador_2' => null,
        ]);

        $pdfService = Mockery::mock(VacationPdfService::class);
        $pdfService->shouldReceive('generate')
            ->once()
            ->andReturn('vacation-test.pdf');
        $this->app->instance(VacationPdfService::class, $pdfService);

        $response = $this->postJson('/api/vacations', [
            'email' => 'empleado@tema.com.pe',
            'last_name' => 'MONROY',
            'mother_name' => 'SOVERO',
            'first_name' => 'LUIS',
            'second_name' => 'FERNANDO',
            'dni' => '09739991',
            'area' => 'Sistemas',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(3)->next('Sunday')->format('Y-m-d'),
            'start_time' => now()->addDay()->format('Y-m-d 08:00:00'),
            'end_time' => now()->addDays(3)->next('Sunday')->format('Y-m-d 18:00:00'),
            'confirmation_image' => UploadedFile::fake()->image('firma.jpg'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('notifications.employee.sent', true);
        $response->assertJsonPath('notifications.employee.recipient', 'empleado@tema.com.pe');

        $this->assertDatabaseHas('vacation_requests', [
            'dni' => '09739991',
            'email' => 'empleado@tema.com.pe',
            'pdf_file' => 'vacation-test.pdf',
        ]);

        Mail::assertSent(VacationEmployeeConfirmationMail::class, function (VacationEmployeeConfirmationMail $mail): bool {
            return $mail->hasTo('empleado@tema.com.pe');
        });

        Mail::assertSent(VacationHrNotificationMail::class, function (VacationHrNotificationMail $mail): bool {
            return $mail->hasTo('rrhh@tema.com.pe');
        });

        Mail::assertSent(VacationApproverNotificationMail::class, 2);
        Mail::assertSent(VacationApproverNotificationMail::class, function (VacationApproverNotificationMail $mail): bool {
            return $mail->hasTo('jefe1@tema.com.pe');
        });
        Mail::assertSent(VacationApproverNotificationMail::class, function (VacationApproverNotificationMail $mail): bool {
            return $mail->hasTo('jefe2@tema.com.pe');
        });

        $this->assertSame(1, VacationRequest::query()->count());
    }
}
