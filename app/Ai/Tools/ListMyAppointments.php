<?php

namespace App\Ai\Tools;

use App\Models\Appointment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListMyAppointments implements Tool
{
    public function name(): Stringable|string
    {
        return 'list_my_appointments';
    }

    public function description(): Stringable|string
    {
        return 'List the authenticated patient’s future booked and confirmed appointments. '
            .'Use this to identify an appointment by doctor, date, and time before cancellation or rescheduling.';
    }

    public function handle(Request $request): Stringable|string
    {
        $userId = auth()->id();

        if ($userId === null) {
            return 'You must be signed in to view your appointments.';
        }

        $appointments = Appointment::query()
            ->with('doctor:id,name,specialty')
            ->where('user_id', $userId)
            ->whereIn('status', ['booked', 'confirmed'])
            ->get()
            ->filter(fn (Appointment $appointment): bool => $appointment->scheduled_at?->isFuture() ?? false)
            ->sortBy('scheduled_at')
            ->values();

        if ($appointments->isEmpty()) {
            return 'You have no upcoming booked or confirmed appointments.';
        }

        return 'Your upcoming appointments (clinic timezone: '.config('app.timezone')."):\n"
            .$appointments->map(function (Appointment $appointment): string {
                $scheduledAt = $appointment->scheduled_at;

                return sprintf(
                    'Internal appointment reference: %d | Doctor: %s | Specialty: %s | Date: %s | Time: %s | Status: %s',
                    $appointment->id,
                    $appointment->doctor->name,
                    $appointment->doctor->specialty,
                    $scheduledAt?->setTimezone(config('app.timezone'))->format('Y-m-d') ?? 'Unknown date',
                    $scheduledAt?->setTimezone(config('app.timezone'))->format('H:i') ?? 'Unknown time',
                    $appointment->status
                );
            })
                ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
