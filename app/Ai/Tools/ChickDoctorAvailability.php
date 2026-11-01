<?php

namespace App\Ai\Tools;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class ChickDoctorAvailability implements Tool
{
    /**
     * Return the tool name exposed to the AI model.
     */
    public function name(): Stringable|string
    {
        return 'check_doctor_availability';
    }

    /**
     * Explain when the AI should use this tool.
     */
    public function description(): Stringable|string
    {
        return 'Check real appointment availability for a doctor registered in the clinic database. '
            . 'Call list_doctors first to obtain a valid doctor ID. '
            . 'Use a date in YYYY-MM-DD format. Set days to 1 to check one day, '
            . 'or use a larger value to scan subsequent days for alternative slots. '
            . 'Only report slots returned by this tool as available.';
    }

    /**
     * Check a doctor's availability over the requested date range.
     */
    public function handle(Request $request): Stringable|string
    {
        // Validate tool arguments before querying the database.
        $validator = Validator::make([
            'doctor_id' => $request['doctor_id'],
            'date' => $request['date'],
            'days' => $request['days'] ?? 1,
        ], [
            'doctor_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
            'days' => ['required', 'integer', 'min:1', 'max:14'],
        ]);

        if ($validator->fails()) {
            return 'Invalid availability request. Provide a valid doctor ID, '
                . 'a date in YYYY-MM-DD format, and a day range between 1 and 14.';
        }

        $doctorId = (int) $request['doctor_id'];
        $days = (int) ($request['days'] ?? 1);

        // Only query doctors that actually exist in the database.
        $doctor = Doctor::query()->find($doctorId);

        if (!$doctor) {
            return 'Doctor not found. Call list_doctors and use an ID from its results.';
        }

        try {
            // Parse the date using the application's configured timezone.
            $start = Carbon::createFromFormat(
                '!Y-m-d',
                (string) $request['date'],
                config('app.timezone')
            );
        } catch (Throwable $exception) {
            return 'The requested date is invalid. Use YYYY-MM-DD format.';
        }

        if (!$start) {
            return 'The requested date is invalid. Use YYYY-MM-DD format.';
        }

        // Do not offer dates in the past.
        $today = Carbon::today(config('app.timezone'));

        if ($start->lt($today)) {
            return 'The requested date is in the past. Ask the patient for today or a future date.';
        }

        $lines = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);

            $freeSlots = $this->freeSlotsFor($doctor, $date);

            // When scanning multiple days, show only dates with available slots.
            if ($days > 1 && empty($freeSlots)) {
                continue;
            }

            if (empty($freeSlots)) {
                $scheduledSlots = $doctor->slotsFor($date);

                $lines[] = empty($scheduledSlots)
                    ? "{$doctor->name} does not work on {$date->format('l, F j, Y')}."
                    : "{$doctor->name} has no available appointments on {$date->format('l, F j, Y')}.";

                continue;
            }

            $lines[] = "{$date->format('l, F j, Y')}: "
                . implode(', ', $freeSlots);
        }

        if (empty($lines)) {
            return "{$doctor->name} has no available slots in the requested date range. "
                . 'Try checking a later date.';
        }

        return $days > 1
            ? "Available appointment slots for {$doctor->name}:\n"
                . implode("\n", $lines)
            : $lines[0];
    }

    /**
     * Return future appointment slots that are not already booked.
     */
    /**
     * @return list<string>
     */
    protected function freeSlotsFor(Doctor $doctor, Carbon $date): array
    {
        // Retrieve the doctor's actual working slots for the requested date.
        $slots = $doctor->slotsFor($date);

        if (empty($slots)) {
            return [];
        }

        // Retrieve existing appointments for this doctor on this date.
        // This assumes every appointment row represents a reserved slot.
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->addDay()->startOfDay();

        $bookedSlots = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['booked', 'confirmed'])
            ->get(['scheduled_at', 'scheduled_at_timezone'])
            ->filter(function (Appointment $appointment) use ($dayStart, $dayEnd): bool {
                $scheduledAt = $appointment->scheduled_at;

                return $scheduledAt !== null
                    && $scheduledAt->gte($dayStart)
                    && $scheduledAt->lt($dayEnd);
            })
            ->map(fn (Appointment $appointment): string => $appointment->scheduled_at->format('H:i'))
            ->all();

        $now = Carbon::now(config('app.timezone'));

        // Exclude booked slots and appointment times that have already passed.
        $freeSlots = array_filter(
            $slots,
            function ($time) use ($bookedSlots, $date, $now) {
                if (in_array($time, $bookedSlots, true)) {
                    return false;
                }

                try {
                    $slotDateTime = Carbon::createFromFormat(
                        '!Y-m-d H:i',
                        $date->toDateString() . ' ' . $time,
                        config('app.timezone')
                    );
                } catch (Throwable $exception) {
                    return false;
                }

                return $slotDateTime?->gt($now) ?? false;
            }
        );

        // Sort available slots chronologically.
        sort($freeSlots);

        return $freeSlots;
    }

    /**
     * Define the arguments accepted by this tool.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'doctor_id' => $schema->integer()
                ->description('The numeric ID of a doctor returned by list_doctors.')
                ->required(),

            'date' => $schema->string()
                ->description('The starting date in YYYY-MM-DD format.')
                ->required(),

            'days' => $schema->integer()
                ->description(
                    'Number of consecutive days to scan, from 1 to 14. '
                    . 'Use 1 for a single day or a larger number to find alternatives.'
                ),
        ];
    }
}
