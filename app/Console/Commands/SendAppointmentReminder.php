<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('app:send-appointment-reminder')]
#[Description('Send reminder emails for upcoming appointments')]
class SendAppointmentReminder extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Capture the current time once to keep the query boundaries consistent.
        $now = now('UTC');

        // Read the model value so both canonical UTC and unclassified legacy
        // rows use their recorded timezone semantics.
        $appointments = Appointment::query()
            ->with('doctor:id,name,specialty')
            ->where('reminder_opt_in', true)
            ->whereNull('reminder_sent_at')
            ->whereIn('status', ['booked', 'confirmed'])
            ->get()
            ->filter(function (Appointment $appointment) use ($now): bool {
                $scheduledAt = $appointment->scheduled_at;

                return $scheduledAt !== null
                    && $scheduledAt->utc()->isAfter($now)
                    && $scheduledAt->utc()->isBefore($now->copy()->addDay());
            })
            ->sortBy('scheduled_at')
            ->values();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($appointments as $appointment) {
            $lock = Cache::lock("appointment-reminder:{$appointment->id}", 600);

            if (! $lock->get()) {
                continue;
            }

            try {
                // Send the reminder email before marking it as sent.
                Mail::to($appointment->patient_email)
                    ->send(new AppointmentReminderMail($appointment));

                // Record the successful send time.
                $appointment->update([
                    'reminder_sent_at' => now(),
                ]);

                $sentCount++;

                $this->info(
                    "Reminder sent for appointment ID: {$appointment->id}."
                );
            } catch (Throwable $exception) {
                // Keep reminder_sent_at null so the reminder can be retried later.
                report($exception);

                $failedCount++;

                $this->error(
                    "Failed to send reminder for appointment ID: {$appointment->id}."
                );
            } finally {
                $lock->release();
            }
        }

        $this->info("{$sentCount} reminder(s) sent.");
        if ($failedCount > 0) {
            $this->warn("{$failedCount} reminder(s) failed.");
        }

        return $failedCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
