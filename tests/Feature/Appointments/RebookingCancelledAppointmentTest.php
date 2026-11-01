<?php

namespace Tests\Feature\Appointments;

use App\Ai\Tools\BookAppointment;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class RebookingCancelledAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_slot_can_be_booked_again(): void
    {
        $firstPatient = User::factory()->create();
        $secondPatient = User::factory()->create();
        $appointmentDate = Carbon::tomorrow()->startOfDay()->addHours(10);
        $doctor = Doctor::create([
            'name' => 'Dr. Rebooking',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $doctor->availabilities()->create([
            'day_of_week' => $appointmentDate->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
        $tool = new BookAppointment;
        $arguments = [
            'doctor_id' => $doctor->id,
            'date' => $appointmentDate->toDateString(),
            'time' => '10:00',
            'patient_name' => 'First Patient',
            'patient_email' => $firstPatient->email,
        ];

        $this->actingAs($firstPatient);
        $firstBooking = $tool->handle(new Request($arguments));
        $firstAppointment = Appointment::query()->sole();
        $firstAppointment->update(['status' => 'cancelled']);

        $this->actingAs($secondPatient);
        $secondBooking = $tool->handle(new Request([
            ...$arguments,
            'patient_name' => 'Second Patient',
            'patient_email' => $secondPatient->email,
        ]));

        $this->assertStringContainsString('successfully booked', (string) $firstBooking);
        $this->assertStringContainsString('successfully booked', (string) $secondBooking);
        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'scheduled_at' => $appointmentDate->utc()->toDateTimeString(),
            'status' => 'cancelled',
            'patient_email' => $firstPatient->email,
        ]);
        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'scheduled_at' => $appointmentDate->utc()->toDateTimeString(),
            'status' => 'booked',
            'patient_email' => $secondPatient->email,
        ]);
    }
}
