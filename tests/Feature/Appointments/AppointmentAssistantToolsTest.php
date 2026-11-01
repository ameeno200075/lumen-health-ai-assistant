<?php

namespace Tests\Feature\Appointments;

use App\Ai\Tools\CancelAppointment;
use App\Ai\Tools\ListMyAppointments;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class AppointmentAssistantToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_my_appointments_returns_one_owned_upcoming_appointment_without_requiring_an_id(): void
    {
        $user = User::factory()->create();
        $doctor = Doctor::create([
            'name' => 'Dr. Natural Language',
            'specialty' => 'Cardiology',
            'bio' => null,
        ]);
        $appointment = $this->createAppointment($user, $doctor, Carbon::tomorrow(config('app.timezone'))->setTime(10, 0));

        $this->actingAs($user);
        $result = (string) (new ListMyAppointments)->handle(new Request([]));

        $this->assertStringContainsString('Dr. Natural Language', $result);
        $this->assertStringContainsString($appointment->scheduled_at->format('Y-m-d'), $result);
        $this->assertStringContainsString($appointment->scheduled_at->format('H:i'), $result);
        $this->assertStringContainsString((string) $appointment->id, $result);
    }

    public function test_list_my_appointments_returns_all_matching_owned_appointments_and_excludes_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $doctor = Doctor::create([
            'name' => 'Dr. Multiple',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $first = $this->createAppointment($user, $doctor, Carbon::tomorrow(config('app.timezone'))->setTime(9, 0));
        $second = $this->createAppointment($user, $doctor, Carbon::tomorrow(config('app.timezone'))->setTime(11, 0));
        $other = $this->createAppointment($otherUser, $doctor, Carbon::tomorrow(config('app.timezone'))->setTime(13, 0));

        $this->actingAs($user);
        $result = (string) (new ListMyAppointments)->handle(new Request([]));

        $this->assertStringContainsString((string) $first->id, $result);
        $this->assertStringContainsString((string) $second->id, $result);
        $this->assertStringNotContainsString((string) $other->id, $result);
    }

    public function test_cancellation_requires_confirmation_and_then_validates_ownership(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $doctor = Doctor::create([
            'name' => 'Dr. Cancellation',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $appointment = $this->createAppointment($user, $doctor, Carbon::tomorrow(config('app.timezone'))->setTime(10, 0));

        $this->actingAs($user);
        $declined = (new CancelAppointment)->handle(new Request([
            'appointment_id' => $appointment->id,
            'confirmed' => false,
        ]));
        $this->assertStringContainsString('explicit patient confirmation', (string) $declined);
        $this->assertSame('booked', $appointment->fresh()->status);

        $this->actingAs($otherUser);
        $notOwned = (new CancelAppointment)->handle(new Request([
            'appointment_id' => $appointment->id,
            'confirmed' => true,
        ]));
        $this->assertStringContainsString('could not find', (string) $notOwned);
        $this->assertSame('booked', $appointment->fresh()->status);
    }

    private function createAppointment(User $user, Doctor $doctor, Carbon $date): Appointment
    {
        return Appointment::create([
            'doctor_id' => $doctor->id,
            'user_id' => $user->id,
            'patient_name' => $user->name,
            'patient_email' => $user->email,
            'scheduled_at' => $date,
            'status' => 'booked',
        ]);
    }
}
