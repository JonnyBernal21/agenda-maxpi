<?php

namespace App\Services;

use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Vehicle;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class StudentSlotAvailabilityService
{
    public function __construct(
        private readonly SameDayScheduleCutoff $sameDayCutoff,
    ) {}

    /**
     * @return list<array{
     *     date: string,
     *     time: string,
     *     cupos: int,
     *     free_instructors: int,
     *     free_vehicles: int
     * }>
     */
    public function availableSlotsForRange(string $startDate, string $endDate): array
    {
        $instructorIds = Instructor::query()->pluck('id')->all();
        $vehicleIds = Vehicle::query()->pluck('id')->all();

        if ($instructorIds === [] || $vehicleIds === []) {
            return [];
        }

        $busy = $this->busyLookup(
            Reservas::query()
                ->active()
                ->whereBetween('date', [$startDate, $endDate])
                ->get(['instructor_id', 'vehicle_id', 'date', 'time'])
        );

        $slots = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        $now = $this->sameDayCutoff->now();
        $today = $now->toDateString();
        $nowTime = $now->format('H:i');

        foreach ($period as $day) {
            $date = $day->toDateString();

            if ($date < $today || $this->sameDayCutoff->blocksDate($date)) {
                continue;
            }

            foreach (Reservas::availableTimes() as $time) {
                $time = $this->normalizeTime($time);

                if ($date === $today && $time <= $nowTime) {
                    continue;
                }

                $availability = $this->availabilityAt($instructorIds, $vehicleIds, $date, $time, $busy);

                if ($availability['cupos'] > 0) {
                    $slots[] = [
                        'date' => $date,
                        'time' => $time,
                        'cupos' => $availability['cupos'],
                        'free_instructors' => $availability['free_instructors'],
                        'free_vehicles' => $availability['free_vehicles'],
                    ];
                }
            }
        }

        return $slots;
    }

    /**
     * @return array{
     *     instructor_ids: list<int>,
     *     vehicle_ids: list<int>,
     *     pairs: list<array{instructor_id: int, vehicle_id: int}>,
     *     cupos: int
     * }
     */
    public function availableOptionsForSlot(string $date, string $time): array
    {
        if ($this->sameDayCutoff->blocksDate($date)) {
            return [
                'available' => false,
                'instructor_ids' => [],
                'vehicle_ids' => [],
                'pairs' => [],
                'cupos' => 0,
            ];
        }

        $time = $this->normalizeTime($time);
        $instructorIds = Instructor::query()->pluck('id')->all();
        $vehicleIds = Vehicle::query()->pluck('id')->all();

        $busy = $this->busyLookup(
            Reservas::query()
                ->active()
                ->where('date', $date)
                ->get(['instructor_id', 'vehicle_id', 'date', 'time'])
        );

        $pairs = [];
        $availableInstructors = [];
        $availableVehicles = [];

        foreach ($instructorIds as $instructorId) {
            foreach ($vehicleIds as $vehicleId) {
                if ($this->isPairFree((int) $instructorId, (int) $vehicleId, $date, $time, $busy)) {
                    $availableInstructors[(int) $instructorId] = true;
                    $availableVehicles[(int) $vehicleId] = true;
                    $pairs[] = [
                        'instructor_id' => (int) $instructorId,
                        'vehicle_id' => (int) $vehicleId,
                    ];
                }
            }
        }

        return [
            'available' => $pairs !== [],
            'instructor_ids' => array_keys($availableInstructors),
            'vehicle_ids' => array_keys($availableVehicles),
            'pairs' => $pairs,
            'cupos' => count($pairs),
        ];
    }

    /**
     * @param  list<int|string>  $instructorIds
     * @param  list<int|string>  $vehicleIds
     * @param  array{instructors: array<string, true>, vehicles: array<string, true>}  $busy
     * @return array{cupos: int, free_instructors: int, free_vehicles: int}
     */
    private function availabilityAt(
        array $instructorIds,
        array $vehicleIds,
        string $date,
        string $time,
        array $busy,
    ): array {
        $time = $this->normalizeTime($time);
        $freeInstructors = 0;
        $freeVehicles = 0;
        $cupos = 0;

        foreach ($instructorIds as $instructorId) {
            if (! isset($busy['instructors'][$this->busyKey($instructorId, $date, $time)])) {
                $freeInstructors++;
            }
        }

        foreach ($vehicleIds as $vehicleId) {
            if (! isset($busy['vehicles'][$this->busyKey($vehicleId, $date, $time)])) {
                $freeVehicles++;
            }
        }

        foreach ($instructorIds as $instructorId) {
            foreach ($vehicleIds as $vehicleId) {
                if ($this->isPairFree((int) $instructorId, (int) $vehicleId, $date, $time, $busy)) {
                    $cupos++;
                }
            }
        }

        return [
            'cupos' => $cupos,
            'free_instructors' => $freeInstructors,
            'free_vehicles' => $freeVehicles,
        ];
    }

    /**
     * @param  Collection<int, Reservas>  $reservas
     * @return array{
     *     instructors: array<string, true>,
     *     vehicles: array<string, true>
     * }
     */
    private function busyLookup(Collection $reservas): array
    {
        $instructors = [];
        $vehicles = [];
        $slotTimes = Reservas::availableTimes();

        foreach ($reservas as $reserva) {
            $reservaTime = $this->normalizeTime($reserva->time);

            foreach ($slotTimes as $slotTime) {
                $slotTime = $this->normalizeTime($slotTime);

                if (! Reservas::slotsOverlap($reservaTime, $slotTime)) {
                    continue;
                }

                $instructors[$this->busyKey($reserva->instructor_id, $reserva->date, $slotTime)] = true;
                $vehicles[$this->busyKey($reserva->vehicle_id, $reserva->date, $slotTime)] = true;
            }
        }

        return [
            'instructors' => $instructors,
            'vehicles' => $vehicles,
        ];
    }

    /**
     * @param  array{instructors: array<string, true>, vehicles: array<string, true>}  $busy
     */
    private function isPairFree(int $instructorId, int $vehicleId, string $date, string $time, array $busy): bool
    {
        $time = $this->normalizeTime($time);

        return ! isset($busy['instructors'][$this->busyKey($instructorId, $date, $time)])
            && ! isset($busy['vehicles'][$this->busyKey($vehicleId, $date, $time)]);
    }

    private function busyKey(int|string $resourceId, string $date, string $time): string
    {
        return "{$resourceId}|{$date}|{$this->normalizeTime($time)}";
    }

    private function normalizeTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
