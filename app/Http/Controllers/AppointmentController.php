<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;
use Throwable;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $appointments = Appointment::query()
            ->with('doctor:id,name,specialty')
            ->where('user_id', $request->user()->id)
            ->orderBy('scheduled_at')
            ->get();

        return inertia('appointment/index', [
            'appointments' => $appointments,
        ]);
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        $appointment->update([
            'status' => 'cancelled',
            'reminder_opt_in' => false,
        ]);

        return back()->with('success', 'Appointment cancelled successfully.');
    }

    public function reschedule(
        Request $request,
        Appointment $appointment
    ): RedirectResponse {
        Gate::authorize('reschedule', $appointment);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);
        $timezone = config('app.timezone');

        try {
            $scheduledAt = Carbon::createFromFormat(
                '!Y-m-d H:i',
                "{$data['date']} {$data['time']}",
                $timezone
            );
        } catch (Throwable) {
            return back()->withErrors([
                'scheduled_at' => 'The requested appointment time is invalid.',
            ]);
        }

        if ($scheduledAt->lte(Carbon::now($timezone))) {
            return back()->withErrors([
                'scheduled_at' => 'The requested appointment time must be in the future.',
            ]);
        }

        $result = DB::transaction(function () use ($appointment, $scheduledAt) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $doctor = $lockedAppointment->doctor()
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedAppointment->status, ['booked', 'confirmed'], true)) {
                return 'INVALID_STATUS';
            }

            if (! in_array($scheduledAt->format('H:i'), $doctor->slotsFor($scheduledAt), true)) {
                return 'INVALID_SLOT';
            }

            $exists = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('id', '!=', $lockedAppointment->id)
                ->where('scheduled_at', $scheduledAt->utc()->toDateTimeString())
                ->whereIn('status', ['booked', 'confirmed'])
                ->exists();

            if ($exists) {
                return 'ALREADY_BOOKED';
            }

            $lockedAppointment->update([
                'scheduled_at' => $scheduledAt,
                'reminder_sent_at' => null,
            ]);

            return 'OK';
        });

        if ($result !== 'OK') {
            return back()->withErrors([
                'scheduled_at' => match ($result) {
                    'ALREADY_BOOKED' => 'That appointment slot is already booked.',
                    'INVALID_SLOT' => 'That time is outside the doctor’s availability.',
                    default => 'This appointment can no longer be rescheduled.',
                },
            ]);
        }

        return back()->with('success', 'Appointment rescheduled successfully.');
    }
}
