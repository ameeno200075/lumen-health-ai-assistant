<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Appointment;
use App\Models\DoctorAvailability;

#[Fillable(['name', 'specialty', 'bio'])]
class Doctor extends Model
{
    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<DoctorAvailability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class);
    }

    /**
     * @return list<string>
     */
    public function slotsFor(Carbon $date): array
    {
        $availability = $this->availabilities->firstWhere(
            'day_of_week',
            $date->dayOfWeek
        );

        if (! $availability) {
            return [];
        }

        $slots = [];

        $startTime = Carbon::parse($availability->start_time);
        $endTime = Carbon::parse($availability->end_time);

        for (
            $time = $startTime->copy();
            $time < $endTime;
            $time->addHour()
        ) {
            $slots[] = $time->format('H:i');
        }

        return $slots;
    }
}
