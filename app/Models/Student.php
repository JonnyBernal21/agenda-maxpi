<?php

namespace App\Models;

use App\Models\Concerns\HasUppercasePersonFields;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, HasUppercasePersonFields, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'institution_id',
        'name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reservas::class, 'student_id');
    }

    public function extraClasses(): HasMany
    {
        return $this->hasMany(StudentExtraClass::class);
    }

    public function completedClassesCount(): int
    {
        return $this->reservas()
            ->where('status', 'completada')
            ->count();
    }

    /**
     * Citas activas (pendiente, confirmada o completada) que ocupan cupo del curso.
     */
    public function bookedClassesCount(): int
    {
        return $this->reservas()
            ->where('status', '!=', 'cancelada')
            ->count();
    }

    /** @deprecated Use completedClassesCount() */
    public function usedClassesCount(): int
    {
        return $this->completedClassesCount();
    }

    public function extraClassesCount(): int
    {
        if ($this->relationLoaded('extraClasses')) {
            return (int) $this->extraClasses->sum('quantity');
        }

        return (int) $this->extraClasses()->sum('quantity');
    }

    public function allowedClassesCount(): int
    {
        return ($this->course?->num_classes ?? 0) + $this->extraClassesCount();
    }

    public function remainingClasses(): int
    {
        return max(0, $this->allowedClassesCount() - $this->bookedClassesCount());
    }

    public function canReserve(): bool
    {
        return $this->course !== null && $this->remainingClasses() > 0;
    }

    public function fullName(): string
    {
        return trim($this->name.' '.$this->last_name);
    }
}
