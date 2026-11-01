<?php

namespace Tests\Feature\Appointments;

use App\Ai\Tools\BookAppointment;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class AppointmentTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_stores_utc_and_returns_the_clinic_local_time(): void
    {
        $this->assertSame('Asia/Damascus', config('app.timezone'));

        $patient = User::factory()->create();
        $appointmentDate = Carbon::tomorrow(config('app.timezone'))->setTime(10, 0);
        $doctor = Doctor::create([
            'name' => 'Dr. Timezone',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $doctor->availabilities()->create([
            'day_of_week' => $appointmentDate->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->actingAs($patient);
        (new BookAppointment())->handle(new Request([
            'doctor_id' => $doctor->id,
            'date' => $appointmentDate->toDateString(),
            'time' => '10:00',
            'patient_name' => 'Timezone Patient',
            'patient_email' => $patient->email,
        ]));

        $appointment = Appointment::query()->sole();

        $this->assertSame($appointmentDate->utc()->toDateTimeString(), $appointment->getRawOriginal('scheduled_at'));
        $this->assertSame('UTC', $appointment->getRawOriginal('scheduled_at_timezone'));
        $this->assertSame(
            '10:00',
            $appointment->scheduled_at
                ->setTimezone(config('app.timezone'))
                ->format('H:i')
        );
        $serializedOnce = $appointment->toArray()['scheduled_at'];
        $serializedTwice = $appointment->toArray()['scheduled_at'];
        $this->assertSame($serializedOnce, $serializedTwice);
        $this->assertSame($appointmentDate->utc()->toIso8601String(), Carbon::parse($serializedTwice)->toIso8601String());
    }

    public function test_rescheduling_uses_the_same_utc_semantics_as_booking(): void
    {
        $patient = User::factory()->create();
        $appointmentDate = Carbon::tomorrow(config('app.timezone'))->setTime(10, 0);
        $newDate = $appointmentDate->copy()->addDay();
        $doctor = Doctor::create([
            'name' => 'Dr. Reschedule Timezone',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $doctor->availabilities()->createMany([
            [
                'day_of_week' => $appointmentDate->dayOfWeek,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
            [
                'day_of_week' => $newDate->dayOfWeek,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
        ]);

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'user_id' => $patient->id,
            'patient_name' => 'Timezone Patient',
            'patient_email' => $patient->email,
            'scheduled_at' => $appointmentDate,
            'status' => 'booked',
        ]);

        $this->actingAs($patient)->put(route('appointments.reschedule', $appointment), [
            'date' => $newDate->toDateString(),
            'time' => '10:00',
        ])->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame($newDate->utc()->toDateTimeString(), $appointment->getRawOriginal('scheduled_at'));
        $this->assertSame('UTC', $appointment->getRawOriginal('scheduled_at_timezone'));
        $this->assertSame('10:00', $appointment->scheduled_at->setTimezone(config('app.timezone'))->format('H:i'));
    }

    public function test_unclassified_historical_rows_are_not_rewritten_and_are_displayed_as_clinic_local(): void
    {
        $doctor = Doctor::create([
            'name' => 'Dr. Historical',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $patient = User::factory()->create();
        $id = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctor->id,
            'user_id' => $patient->id,
            'patient_name' => 'Historical Patient',
            'patient_email' => $patient->email,
            'scheduled_at' => '2026-11-04 10:00:00',
            'scheduled_at_timezone' => null,
            'status' => 'booked',
            'reminder_opt_in' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment = Appointment::query()->findOrFail($id);

        $this->assertSame('2026-11-04 10:00:00', $appointment->getRawOriginal('scheduled_at'));
        $this->assertNull($appointment->getRawOriginal('scheduled_at_timezone'));
        $this->assertSame('10:00', $appointment->scheduled_at->format('H:i'));
        $this->assertSame('2026-11-04T07:00:00.000000Z', $appointment->toArray()['scheduled_at']);
    }

    public function test_timezone_conversion_uses_iana_rules_without_a_fixed_hour_offset(): void
    {
        $local = Carbon::create(2026, 11, 4, 10, 0, 0, 'Asia/Damascus');

        $this->assertSame(
            $local->copy()->utc()->toIso8601String(),
            Carbon::parse($local->toIso8601String())->setTimezone('UTC')->toIso8601String()
        );
        $this->assertSame('10:00', $local->copy()->utc()->setTimezone('Asia/Damascus')->format('H:i'));
    }
}
