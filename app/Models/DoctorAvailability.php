<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $doctor_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 */
#[Fillable(['doctor_id', 'day_of_week', 'start_time', 'end_time'])]
class DoctorAvailability extends Model
{
    protected $casts = [
        'day_of_week' => 'integer',

    ];

    /**
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
