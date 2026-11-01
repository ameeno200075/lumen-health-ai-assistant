<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Seed the database with sample clinic doctors.
     */
    public function run(): void
    {
        // Keep the existing doctors and add the new sample doctors.
        $doctors = [
            [
                'name' => 'Dr. John Doe',
                'specialty' => 'Cardiology',
                'bio' => 'Specializes in heart health and cardiovascular conditions.',
            ],
            [
                'name' => 'Dr. Jane Smith',
                'specialty' => 'Dermatology',
                'bio' => 'Focuses on skin, hair, and nail conditions.',
            ],
            [
                'name' => 'Dr. Michael Brown',
                'specialty' => 'Pediatrics',
                'bio' => 'Provides medical care for infants, children, and adolescents.',
            ],
            [
                'name' => 'Dr. Sarah Wilson',
                'specialty' => 'Neurology',
                'bio' => 'Specializes in conditions affecting the brain and nervous system.',
            ],
            [
                'name' => 'Dr. David Miller',
                'specialty' => 'Orthopedics',
                'bio' => 'Focuses on bone, joint, and musculoskeletal conditions.',
            ],
            [
                'name' => 'Dr. Emily Davis',
                'specialty' => 'Ophthalmology',
                'bio' => 'Specializes in eye health and vision-related conditions.',
            ],
            [
                'name' => 'Dr. James Taylor',
                'specialty' => 'Dentistry',
                'bio' => 'Provides preventive and general dental care.',
            ],
            [
                'name' => 'Dr. Olivia Martin',
                'specialty' => 'Gynecology',
                'bio' => 'Focuses on reproductive and gynecological health.',
            ],
            [
                'name' => 'Dr. Daniel Anderson',
                'specialty' => 'Internal Medicine',
                'bio' => 'Provides care for a broad range of adult medical conditions.',
            ],
            [
                'name' => 'Dr. Sophia Thomas',
                'specialty' => 'Psychiatry',
                'bio' => 'Specializes in mental health assessment and treatment.',
            ],
            [
                'name' => 'Dr. William Jackson',
                'specialty' => 'ENT',
                'bio' => 'Focuses on ear, nose, and throat conditions.',
            ],
            [
                'name' => 'Dr. Isabella White',
                'specialty' => 'Urology',
                'bio' => 'Specializes in urinary tract and urological conditions.',
            ],
            [
                'name' => 'Dr. Benjamin Harris',
                'specialty' => 'Endocrinology',
                'bio' => 'Focuses on hormonal and endocrine system conditions.',
            ],
            [
                'name' => 'Dr. Mia Clark',
                'specialty' => 'Gastroenterology',
                'bio' => 'Specializes in digestive system conditions.',
            ],
            [
                'name' => 'Dr. Alexander Lewis',
                'specialty' => 'Pulmonology',
                'bio' => 'Focuses on lung and respiratory conditions.',
            ],
        ];

        foreach ($doctors as $doctor) {
            // Match by name so rerunning the seeder does not create duplicates.
            Doctor::updateOrCreate(
                ['name' => $doctor['name']],
                $doctor
            );
        }
    }
}
