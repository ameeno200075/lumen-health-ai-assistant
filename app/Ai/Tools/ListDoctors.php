<?php

namespace App\Ai\Tools;

use App\Models\Doctor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListDoctors implements Tool
{
    /**
     * Define the tool name exposed to the AI model.
     */
    public function name(): Stringable|string
    {
        return 'list_doctors';
    }

    /**
     * Explain when and why the AI should use this tool.
     */
    public function description(): Stringable|string
    {
        return 'Retrieve real doctors registered in the clinic database. '
            .'Use this tool before recommending a doctor or checking appointment availability. '
            .'Only doctors returned by this tool may be recommended or booked. '
            .'Each result includes the doctor ID, name, specialty, and biography.';
    }

    /**
     * Retrieve doctors from the database, optionally filtering by specialty.
     */
    public function handle(Request $request): Stringable|string
    {
        // Read and normalize the optional specialty parameter.
        $specialty = trim((string) ($request['specialty'] ?? ''));

        // Query the database instead of generating doctor information.
        $doctors = Doctor::query()
            ->when(
                $specialty !== '',
                fn ($query) => $query->where(
                    'specialty',
                    'like',
                    '%'.addcslashes($specialty, '\\%_').'%'
                )
            )
            ->orderBy('name')
            ->get(['id', 'name', 'specialty', 'bio']);

        // Explicitly report when no matching doctors exist.
        if ($doctors->isEmpty()) {
            return $specialty !== ''
                ? "No doctors were found for the specialty: {$specialty}. "
                    .'Do not invent a doctor or assume that this specialty is available.'
                : 'No doctors are currently registered in the clinic database. '
                    .'Do not invent doctor names or specialties.';
        }

        // Return only information retrieved from the database.
        return "Doctors registered in the clinic database:\n"
            .$doctors->map(
                fn ($doctor) => sprintf(
                    'ID: %d | Name: %s | Specialty: %s | Bio: %s',
                    $doctor->id,
                    $doctor->name,
                    $doctor->specialty ?: 'Not specified',
                    $doctor->bio ?: 'No biography available'
                )
            )->implode("\n");
    }

    /**
     * Define the input parameters accepted by this tool.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'specialty' => $schema->string()
                ->description(
                    'Optional specialty filter, such as Cardiology or Dermatology. '
                    .'Omit this parameter to retrieve all registered doctors.'
                ),
        ];
    }
}
