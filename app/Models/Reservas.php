<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservas extends Model
{
    public const CLASS_DURATION_MINUTES = 120;

    /** @var list<string> */
    public const AVAILABLE_TIMES = ['08:00', '10:00', '12:00', '14:00', '16:00'];

    protected $table = 'reservas';

    /**
     * @return list<string>
     */
    public static function availableTimes(): array
    {
        return self::AVAILABLE_TIMES;
    }

    /**
     * @return list<string>
     */
    public static function halfHourTimes(): array
    {
        $times = [];

        for ($hour = 8; $hour <= 19; $hour++) {
            $times[] = sprintf('%02d:00', $hour);

            if ($hour < 19) {
                $times[] = sprintf('%02d:30', $hour);
            }
        }

        return $times;
    }

    public static function normalizeTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    public static function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', self::normalizeTime($time)));

        return ($hour * 60) + $minute;
    }

    public static function minutesToTime(int $minutes): string
    {
        $hour = intdiv(max(0, $minutes), 60);
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }

    public static function slotEndTime(string $time): string
    {
        return self::minutesToTime(self::timeToMinutes($time) + self::CLASS_DURATION_MINUTES);
    }

    public static function slotsOverlap(string $firstTime, string $secondTime): bool
    {
        $firstStart = self::timeToMinutes($firstTime);
        $firstEnd = $firstStart + self::CLASS_DURATION_MINUTES;
        $secondStart = self::timeToMinutes($secondTime);
        $secondEnd = $secondStart + self::CLASS_DURATION_MINUTES;

        return $firstStart < $secondEnd && $firstEnd > $secondStart;
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'instructor_id',
        'vehicle_id',
        'date',
        'time',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * @param  Builder<Reservas>  $query
     * @return Builder<Reservas>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelada');
    }

    public function startsAt(): string
    {
        return "{$this->date} {$this->time}:00";
    }

    public function endsAt(?int $durationMinutes = null): string
    {
        $durationMinutes ??= self::CLASS_DURATION_MINUTES;

        return date('Y-m-d H:i:s', strtotime($this->startsAt()." +{$durationMinutes} minutes"));
    }
}
