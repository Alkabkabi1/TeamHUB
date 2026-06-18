<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\EventAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_attendance_id' => EventAttendance::factory()->checkedIn()->forPastEvent(),
            'file_path' => 'certificates/'.fake()->uuid().'.pdf',
        ];
    }
}
