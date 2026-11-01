<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/doctors/index', [
            'doctors' => Doctor::with('availabilities')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function validateDoctor(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'specialty' => ['required', 'string', 'max:255'],

            'availabilities' => ['nullable', 'array'],
            'availabilities.*.day_of_week' => [
                'required',
                'integer',
                'between:0,6',
            ],
            'availabilities.*.start_time' => [
                'required',
                'date_format:H:i',
            ],
            'availabilities.*.end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail) use ($request): void {
                    preg_match('/availabilities\.(\d+)\.end_time/', $attribute, $matches);

                    $index = $matches[1] ?? null;
                    $startTime = $index === null
                        ? null
                        : data_get($request->input('availabilities'), "{$index}.start_time");

                    if ($startTime !== null && $value <= $startTime) {
                        $fail('The end time must be after the start time.');
                    }
                },
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDoctor($request);

        DB::transaction(function () use ($data): void {
            $doctor = Doctor::create(
                Arr::only($data, ['name', 'bio', 'specialty'])
            );

            $this->syncAvailabilities(
                $doctor,
                $data['availabilities'] ?? []
            );
        });

        return redirect()
            ->route('admin.doctors.index')
            ->with('success', 'Doctor created successfully.');
    }

    public function update(
        Request $request,
        Doctor $doctor
    ): RedirectResponse {
        $data = $this->validateDoctor($request);

        DB::transaction(function () use ($data, $doctor): void {
            $doctor->update(
                Arr::only($data, ['name', 'bio', 'specialty'])
            );

            $this->syncAvailabilities(
                $doctor,
                $data['availabilities'] ?? []
            );
        });

        return redirect()
            ->route('admin.doctors.index')
            ->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        if ($doctor->appointments()->exists()) {
            return back()->withErrors([
                'doctor' => 'Doctors with appointment history cannot be deleted.',
            ]);
        }

        $doctor->delete();

        return redirect()
            ->route('admin.doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $availabilities
     */
    public function syncAvailabilities(
        Doctor $doctor,
        array $availabilities
    ): void {
        $doctor->availabilities()->delete();

        foreach ($availabilities as $availability) {
            $doctor->availabilities()->create($availability);
        }
    }
}
