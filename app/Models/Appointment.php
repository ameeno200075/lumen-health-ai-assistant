<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'user_id', 'patient_name', 'patient_email',
    'scheduled_at', 'scheduled_at_timezone', 'reason', 'status', 'reminder_opt_in', 'reminder_sent_at'])]
class Appointment extends Model
{
    protected $casts = [
        'reminder_opt_in' => 'boolean',
        'reminder_sent_at' => 'datetime',
    ];

    /**
     * Read canonical UTC rows as UTC and preserve unclassified legacy rows as
     * clinic-local wall-clock values without rewriting their stored timestamp.
     *
     * @return Attribute<Carbon|null, Carbon|string|null>
     */
    protected function scheduledAt(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?Carbon {
                if ($value === null) {
                    return null;
                }

                $timezone = $this->getRawOriginal('scheduled_at_timezone') ?? config('app.timezone');

                return Carbon::parse($value, $timezone);
            },
            set: function (Carbon|string|null $value): array {
                if ($value === null) {
                    return [
                        'scheduled_at' => null,
                        'scheduled_at_timezone' => null,
                    ];
                }

                $scheduledAt = $value instanceof Carbon
                    ? $value->copy()
                    : Carbon::parse($value, config('app.timezone'));

                return [
                    'scheduled_at' => $scheduledAt->utc()->format('Y-m-d H:i:s'),
                    'scheduled_at_timezone' => 'UTC',
                ];
            },
        );
    }

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
