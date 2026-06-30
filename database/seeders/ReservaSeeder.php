<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::query()->with('course')->get();
        $instructors = Instructor::query()->get();
        $vehicles = Vehicle::query()->get();

        if ($students->isEmpty() || $instructors->isEmpty() || $vehicles->isEmpty()) {
            return;
        }

        $times = Reservas::AVAILABLE_TIMES;
        $scheduledSlots = [];

        for ($dayOffset = -7; $dayOffset <= 21; $dayOffset++) {
            $date = Carbon::today()->addDays($dayOffset);
            if ($date->isWeekend()) {
                continue;
            }

            $dailyEvents = random_int(2, 5);

            for ($i = 0; $i < $dailyEvents; $i++) {
                $instructor = $instructors->random();
                $vehicle = $vehicles->random();
                $time = fake()->randomElement($times);

                $slotKey = "{$date->format('Y-m-d')}|{$instructor->id}|{$time}";

                if (isset($scheduledSlots[$slotKey])) {
                    continue;
                }

                $status = match (true) {
                    $date->isPast() => fake()->randomElement(['completada', 'cancelada']),
                    $date->isToday() => fake()->randomElement(['pendiente', 'confirmada', 'completada']),
                    default => fake()->randomElement(['pendiente', 'confirmada']),
                };

                $eligibleStudents = $students->filter(function (Student $student) use ($status) {
                    if ($status === 'cancelada') {
                        return true;
                    }

                    return $student->canReserve();
                });

                if ($eligibleStudents->isEmpty()) {
                    continue;
                }

                $student = $eligibleStudents->random();

                $scheduledSlots[$slotKey] = true;

                Reservas::query()->create([
                    'student_id' => (string) $student->id,
                    'instructor_id' => (string) $instructor->id,
                    'vehicle_id' => (string) $vehicle->id,
                    'date' => $date->format('Y-m-d'),
                    'time' => $time,
                    'status' => $status,
                ]);

                $student->unsetRelation('reservas');
            }
        }
    }
}
