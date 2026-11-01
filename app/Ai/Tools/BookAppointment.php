<?php

namespace App\Ai\Tools;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class BookAppointment implements Tool
{
    /**
     * Return the name used by the AI model to invoke this tool.
     */
    public function name(): Stringable|string
    {
        return 'book_appointment';
    }

    /**
     * Explain the booking requirements to the AI model.
     */
    public function description(): Stringable|string
    {
        return 'Book an appointment with a doctor registered in the clinic database. '
            . 'Only call this tool after the patient has explicitly confirmed the doctor, '
            . 'date, time, full name, and email address. '
            . 'The tool validates the doctor, schedule, and current availability before saving.';
    }

    /**
     * Validate and create an appointment.
     */
    public function handle(Request $request): Stringable|string
    {
        $userId = auth()->id();

        if ($userId === null) {
            return 'You must be signed in to book an appointment.';
        }

        // Validate all user-provided booking fields before processing them.
        $validator = Validator::make([
            'doctor_id' => $request['doctor_id'],
            'date' => $request['date'],
            'time' => $request['time'],
            'patient_name' => $request['patient_name'],
            'patient_email' => $request['patient_email'],
            'reason' => $request['reason'] ?? null,
        ], [
            'doctor_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'patient_name' => ['required', 'string', 'min:2', 'max:255'],
            'patient_email' => ['required', 'email', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return 'The booking details are invalid. Please verify the date, time, '
                . 'patient name, and email address.';
        }

        $timezone = config('app.timezone');

        try {
            // Parse the exact requested appointment date and time.
            $scheduledAt = Carbon::createFromFormat(
                '!Y-m-d H:i',
                $request['date'] . ' ' . $request['time'],
                $timezone
            );
        } catch (Throwable $exception) {
            return 'The requested appointment date or time is invalid.';
        }

        if (!$scheduledAt) {
            return 'The requested appointment date or time is invalid.';
        }

        // Reject appointments that have already passed.
        if ($scheduledAt->lte(Carbon::now($timezone))) {
            return 'That appointment time is no longer in the future. '
                . 'Please choose a future time.';
        }

        try {
            // Use a database transaction to keep the booking operation consistent.
            $appointment = DB::transaction(function () use ($request, $scheduledAt, $userId) {
                // Lock the doctor row while checking and creating the booking.
                $doctor = Doctor::query()
                    ->whereKey((int) $request['doctor_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$doctor) {
                    return null;
                }

                // Verify that the requested time belongs to the doctor's schedule.
                $slots = $doctor->slotsFor($scheduledAt);

                if (!in_array($scheduledAt->format('H:i'), $slots, true)) {
                    return 'INVALID_SLOT';
                }

                // Check whether this slot is already reserved.
                $alreadyBooked = Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('scheduled_at', $scheduledAt->utc()->toDateTimeString())
                    ->whereIn('status', ['booked', 'confirmed'])
                    ->exists();

                if ($alreadyBooked) {
                    return 'ALREADY_BOOKED';
                }

                // Create the appointment only after validating the doctor and slot.
                return Appointment::create([
                    'doctor_id' => $doctor->id,
                    'user_id' => $userId,
                    'patient_name' => $request['patient_name'],
                    'patient_email' => $request['patient_email'],
                    'scheduled_at' => $scheduledAt,
                    'reason' => $request['reason'] ?? null,
                    'status' => 'booked',
                ]);
            });

            if ($appointment === null) {
                return 'The selected doctor does not exist. '
                    . 'Use list_doctors to retrieve a valid doctor ID.';
            }

            if ($appointment === 'INVALID_SLOT') {
                return 'The requested time is not part of this doctor’s schedule. '
                    . 'Use check_doctor_availability to find valid appointment times.';
            }

            if ($appointment === 'ALREADY_BOOKED') {
                return 'This appointment slot has already been booked. '
                    . 'Use check_doctor_availability to find another available time.';
            }

            // Return a success message only after the database confirms creation.
            return "Appointment #{$appointment->id} successfully booked for "
                . "{$request['patient_name']} with doctor ID {$appointment->doctor_id} "
                . "on {$scheduledAt->format('l, F j, Y')} at {$scheduledAt->format('H:i')}. "
                . 'Ask the patient whether they would like an email reminder, '
                . 'then use set_appointment_reminder with this appointment ID.';
        } catch (QueryException $exception) {
            // Handle a possible duplicate booking rejected by a database constraint.
            report($exception);

            return 'The appointment could not be saved, possibly because the slot was '
                . 'booked at the same time by another request. Check availability and try again.';
        }
    }

    /**
     * Define the required booking parameters.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'doctor_id' => $schema->integer()
                ->description('The ID of a doctor returned by list_doctors.')
                ->required(),

            'date' => $schema->string()
                ->format('date')
                ->description('Appointment date in YYYY-MM-DD format.')
                ->required(),

            'time' => $schema->string()
                ->description('Appointment time in 24-hour HH:MM format.')
                ->required(),

            'patient_name' => $schema->string()
                ->description('The patient’s full name.')
                ->required(),

            'patient_email' => $schema->string()
                ->format('email')
                ->description('The patient’s valid email address.')
                ->required(),

            'reason' => $schema->string()
                ->nullable()
                ->description('Optional reason for the appointment.'),
        ];
    }
}
