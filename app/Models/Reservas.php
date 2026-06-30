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
