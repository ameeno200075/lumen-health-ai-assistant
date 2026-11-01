<?php

namespace App\Ai\Tools;

use App\Models\Appointment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SetAppointmentReminder implements Tool
{
    // Give the AI a stable tool name to use when calling this action.
    public function name(): Stringable|string
    {
        return 'set_appointment_reminder';
    }

    // Explain when the AI should use this tool.
    public function description(): Stringable|string
    {
        return 'Enable or disable an email reminder for a booked appointment. '
            . 'Call this after booking, once the patient confirms whether they want '
            . 'an email reminder approximately 24 hours before the appointment.';
    }

    // Update the reminder preference for an appointment.
    public function handle(Request $request): Stringable|string
    {
        $userId = auth()->id();

        if ($userId === null) {
            return 'You must be signed in to manage appointment reminders.';
        }

        // Find the appointment and restrict access to the authenticated user when available.
        $appointment = Appointment::query()
            ->where('id', $request['appointment_id'])
            ->where('user_id', $userId)
            ->whereIn('status', ['booked', 'confirmed'])
            ->first();

        // Do not update anything if the appointment cannot be found.
        if (!$appointment || !$appointment->scheduled_at?->isFuture()) {
            return 'I could not find an appointment to update.';
        }

        // Convert the patient's choice into a boolean value.
        $optIn = (bool) $request['opt_in'];

        // Save the patient's reminder preference in the database.
        $appointment->update([
            'reminder_opt_in' => $optIn,
        ]);

        // Tell the AI what happened so it can respond to the patient.
        return $optIn
            ? "Email reminder enabled for appointment #{$appointment->id}. "
                . "A reminder is requested for {$appointment->patient_email} "
                . "approximately 24 hours before the appointment."
            : "Email reminder disabled for appointment #{$appointment->id}.";
    }

    // Define the structured arguments the AI must provide.
    public function schema(JsonSchema $schema): array
    {
        return [
            'appointment_id' => $schema->integer()
                ->description('The numeric appointment ID returned by book_appointment.')
                ->required(),

            'opt_in' => $schema->boolean()
                ->description('True if the patient wants an email reminder; false otherwise.')
                ->required(),
        ];
    }
}
