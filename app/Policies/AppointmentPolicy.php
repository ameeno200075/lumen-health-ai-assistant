<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $appointment->user_id === $user->id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment)
            && $appointment->scheduled_at->isFuture()
            && in_array($appointment->status, ['booked', 'confirmed'], true);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->cancel($user, $appointment);
    }
}
