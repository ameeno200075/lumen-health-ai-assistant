<?php

namespace Tests\Feature\Appointments;

use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_patient_can_book_a_slot_concurrently(): void
    {
        if (in_array(config('database.default'), ['sqlite'], true)) {
            $this->markTestSkipped(
                'SQLite does not provide realistic cross-process row-lock testing for this suite.'
            );
        }

        $firstPatient = User::factory()->create();
        $secondPatient = User::factory()->create();
        $appointmentDate = Carbon::tomorrow()->startOfDay()->addHours(10);
        $doctor = Doctor::create([
            'name' => 'Dr. Concurrent Booking',
            'specialty' => 'General Medicine',
            'bio' => null,
        ]);
        $doctor->availabilities()->create([
            'day_of_week' => $appointmentDate->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $runner = tempnam(sys_get_temp_dir(), 'concurrent-booking-');
        file_put_contents($runner, <<<'PHP'
<?php

require $argv[1].'/vendor/autoload.php';

$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Auth::loginUsingId((int) $argv[2]);

$result = (new App\Ai\Tools\BookAppointment())->handle(
    new Laravel\Ai\Tools\Request(json_decode($argv[3], true, flags: JSON_THROW_ON_ERROR))
);

file_put_contents($argv[4], (string) $result);
PHP);

        $arguments = json_encode([
            'doctor_id' => $doctor->id,
            'date' => $appointmentDate->toDateString(),
            'time' => '10:00',
            'patient_name' => 'Concurrent Patient',
            'patient_email' => 'concurrent@example.com',
        ], JSON_THROW_ON_ERROR);
        $firstResult = tempnam(sys_get_temp_dir(), 'concurrent-booking-result-');
        $secondResult = tempnam(sys_get_temp_dir(), 'concurrent-booking-result-');
        $processes = [
            new Process([
                PHP_BINARY,
                $runner,
                base_path(),
                (string) $firstPatient->id,
                $arguments,
                $firstResult,
            ]),
            new Process([
                PHP_BINARY,
                $runner,
                base_path(),
                (string) $secondPatient->id,
                $arguments,
                $secondResult,
            ]),
        ];

        try {
            foreach ($processes as $process) {
                $process->start();
            }

            foreach ($processes as $process) {
                $process->wait();
                $this->assertSame('', $process->getErrorOutput());
                $this->assertTrue($process->isSuccessful());
            }

            $results = [
                file_get_contents($firstResult),
                file_get_contents($secondResult),
            ];
            $successes = array_filter(
                $results,
                fn (string|false $result): bool => is_string($result)
                    && str_contains($result, 'successfully booked')
            );
            $rejections = array_filter(
                $results,
                fn (string|false $result): bool => is_string($result)
                    && str_contains($result, 'already been booked')
            );

            $this->assertCount(1, $successes);
            $this->assertCount(1, $rejections);
            $this->assertDatabaseCount('appointments', 1);
            $this->assertDatabaseHas('appointments', [
                'doctor_id' => $doctor->id,
                'scheduled_at' => $appointmentDate->toDateTimeString(),
                'status' => 'booked',
            ]);
        } finally {
            @unlink($runner);
            @unlink($firstResult);
            @unlink($secondResult);
        }
    }
}
