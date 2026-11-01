<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->is_admin, 403);

        return inertia('admin/dashboard', [
            'stats' => [
                'doctors' => Doctor::query()->count(),
                'appointments' => Appointment::query()->count(),
                'upcoming' => Appointment::query()
                    ->whereIn('status', ['booked', 'confirmed'])
                    ->where('scheduled_at', '>', now('UTC'))
                    ->count(),
                'confirmed' => Appointment::query()->where('status', 'confirmed')->count(),
                'cancelled' => Appointment::query()->where('status', 'cancelled')->count(),
            ],
        ]);
    }
}
