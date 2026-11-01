<?php

namespace App\Ai\Tools;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class RescheduleAppointment implements Tool
{
    public function name(): Stringable|string
    {
        return 'reschedule_appointment';
    }

    public function description(): Stringable|string
    {
        return 'Move a future booked or confirmed appointment to an available slot owned by the signed-in patient.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        if ($validator->fails() || auth()->id() === null) {
            return 'The rescheduling details are invalid.';
        }

        $timezone = config('app.timezone');

        try {
            $scheduledAt = Carbon::createFromFormat(
                '!Y-m-d H:i',
                "{$request['date']} {$request['time']}",
                $timezone
            );
        } catch (Throwable) {
            return 'The requested appointment time is invalid.';
        }

        if ($scheduledAt->lte(Carbon::now($timezone))) {
            return 'The requested appointment time must be in the future.';
        }

        $result = DB::transaction(function () use ($request, $scheduledAt) {
            $appointment = Appointment::query()
                ->whereKey($request['appointment_id'])
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (! $appointment || ! in_array($appointment->status, ['booked', 'confirmed'], true)) {
                return 'NOT_FOUND';
            }

            $doctor = Doctor::query()
                ->whereKey($appointment->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($scheduledAt->format('H:i'), $doctor->slotsFor($scheduledAt), true)) {
                return 'INVALID_SLOT';
            }

            $exists = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('id', '!=', $appointment->id)
                ->where('scheduled_at', $scheduledAt->utc()->toDateTimeString())
                ->whereIn('status', ['booked', 'confirmed'])
                ->exists();

            if ($exists) {
                return 'ALREADY_BOOKED';
            }

            $appointment->update([
                'scheduled_at' => $scheduledAt,
                'reminder_sent_at' => null,
            ]);

            return 'OK';
        });

        return match ($result) {
            'OK' => 'Appointment rescheduled successfully.',
            'INVALID_SLOT' => 'The requested time is outside the doctor’s availability.',
            'ALREADY_BOOKED' => 'That appointment slot is already booked.',
            default => 'I could not find a reschedulable appointment for your account.',
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'appointment_id' => $schema->integer()->required(),
            'date' => $schema->string()->description('Date in YYYY-MM-DD format.')->required(),
            'time' => $schema->string()->description('Time in HH:MM format.')->required(),
        ];
    }
}
