<?php

namespace Tests\Feature\Appointments;

use App\Console\Commands\SendAppointmentReminder;
use App\Ai\Tools\SetAppointmentReminder;
use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_appointments_are_reminded_and_cancelled_ones_are_not(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $doctor = Doctor::create([
            'name' => 'Dr. Reminder',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $confirmed = $this->createAppointment($doctor, $user, 'confirmed');
        $cancelled = $this->createAppointment($doctor, $user, 'cancelled');

        $this->artisan('app:send-appointment-reminder')
            ->assertExitCode(0);

        Mail::assertSent(
            AppointmentReminderMail::class,
            fn (AppointmentReminderMail $mail): bool => $mail->appointment->is($confirmed)
        );
        Mail::assertNotSent(
            AppointmentReminderMail::class,
            fn (AppointmentReminderMail $mail): bool => $mail->appointment->is($cancelled)
        );
        $this->assertNotNull($confirmed->fresh()->reminder_sent_at);
        $this->assertNull($cancelled->fresh()->reminder_sent_at);
    }

    public function test_cancelled_appointment_cannot_enable_a_reminder(): void
    {
        $user = User::factory()->create();
        $doctor = Doctor::create([
            'name' => 'Dr. Cancelled Reminder',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $appointment = $this->createAppointment($doctor, $user, 'cancelled');
        $appointment->update(['reminder_opt_in' => false]);

        $this->actingAs($user);
        $result = (new SetAppointmentReminder())->handle(new Request([
            'appointment_id' => $appointment->id,
            'opt_in' => true,
        ]));

        $this->assertStringContainsString('could not find', (string) $result);
        $this->assertFalse($appointment->fresh()->reminder_opt_in);
    }

    private function createAppointment(
        Doctor $doctor,
        User $user,
        string $status
    ): Appointment {
        return Appointment::create([
            'doctor_id' => $doctor->id,
            'user_id' => $user->id,
            'patient_name' => $user->name,
            'patient_email' => $user->email,
            'scheduled_at' => Carbon::now(config('app.timezone'))->addHours(12),
            'status' => $status,
            'reminder_opt_in' => true,
        ]);
    }
}
