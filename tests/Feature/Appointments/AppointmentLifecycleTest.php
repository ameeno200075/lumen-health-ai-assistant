<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_cancel_a_future_appointment(): void
    {
        $user = User::factory()->create();
        $appointment = $this->createAppointment($user);

        $response = $this->actingAs($user)
            ->post(route('appointments.cancel', $appointment));

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'reminder_opt_in' => false,
        ]);
    }

    public function test_patient_cannot_cancel_another_patients_appointment(): void
    {
        $owner = User::factory()->create();
        $otherPatient = User::factory()->create();
        $appointment = $this->createAppointment($owner);

        $this->actingAs($otherPatient)
            ->post(route('appointments.cancel', $appointment))
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'booked',
        ]);
    }

    public function test_owner_can_reschedule_to_an_available_slot(): void
    {
        $user = User::factory()->create();
        $appointment = $this->createAppointment($user);
        $date = Carbon::parse($appointment->scheduled_at)
            ->setTimezone(config('app.timezone'));

        $response = $this->actingAs($user)
            ->put(route('appointments.reschedule', $appointment), [
                'date' => $date->toDateString(),
                'time' => '11:00',
            ]);

        $response->assertRedirect();
        $this->assertSame(
            $date->setTime(11, 0)->utc()->toDateTimeString(),
            $appointment->fresh()->getRawOriginal('scheduled_at')
        );
    }

    private function createAppointment(User $user): Appointment
    {
        $date = Carbon::tomorrow(config('app.timezone'))->setTime(10, 0);
        $doctor = Doctor::create([
            'name' => 'Dr. Lifecycle',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $doctor->availabilities()->create([
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

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
