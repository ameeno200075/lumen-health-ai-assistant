<?php

namespace App\Ai\Tools;

use App\Models\Appointment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CancelAppointment implements Tool
{
    public function name(): Stringable|string
    {
        return 'cancel_appointment';
    }

    public function description(): Stringable|string
    {
        return 'Cancel a future booked or confirmed appointment owned by the signed-in patient.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (! filter_var($request['confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return 'Cancellation requires explicit patient confirmation.';
        }

        $userId = auth()->id();
        $appointment = $userId === null
            ? null
            : Appointment::query()
                ->whereKey($request['appointment_id'])
                ->where('user_id', $userId)
                ->whereIn('status', ['booked', 'confirmed'])
                ->first();

        if (! $appointment || ! $appointment->scheduled_at?->isFuture()) {
            return 'I could not find a cancellable appointment for your account.';
        }

        $appointment->update([
            'status' => 'cancelled',
            'reminder_opt_in' => false,
        ]);

        return "Appointment #{$appointment->id} was cancelled successfully.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'appointment_id' => $schema->integer()
                ->description('The appointment ID returned by the booking flow.')
                ->required(),
            'confirmed' => $schema->boolean()
                ->description('Must be true only after the patient explicitly confirms this exact cancellation.')
                ->required(),
        ];
    }
}
