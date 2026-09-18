<?php

namespace App\Models;

use App\Models\Concerns\HasUppercasePersonFields;
use App\Support\DiscountInput;
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

    public const PAYMENT_CASH = 'efectivo';

    public const PAYMENT_TRANSFER = 'transferencia';

    public const PAYMENT_CARD = 'tarjeta';

    public const HOME_CLASS_FEE = 100.00;

    /**
     * @var array<string, string>
     */
    public const PAYMENT_METHODS = [
        self::PAYMENT_CASH => 'Efectivo',
        self::PAYMENT_TRANSFER => 'Transferencia',
        self::PAYMENT_CARD => 'Tarjeta',
    ];

    /**
     * @var array<int, string>
     */
    public const PAYMENT_PLANS = [
        1 => 'Un solo pago',
        2 => 'Dos pagos',
        3 => 'Tres pagos',
        4 => 'Cuatro pagos',
    ];

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
        'is_home_class',
        'meeting_point',
        'meeting_lat',
        'meeting_lng',
        'payment_subtotal',
        'discount_percent',
        'discount_amount',
        'payment_total',
        'payment_method',
        'payment_plan',
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
            'is_home_class' => 'boolean',
            'meeting_lat' => 'decimal:7',
            'meeting_lng' => 'decimal:7',
            'payment_subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'payment_total' => 'decimal:2',
            'payment_plan' => 'integer',
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

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    public function recordPayment(?string $paidAt = null): ?StudentPayment
    {
        $amount = round((float) $this->payment_total, 2);

        if ($amount <= 0) {
            return null;
        }

        return $this->payments()->create([
            'amount' => $amount,
            'paid_at' => $paidAt ?? now()->toDateString(),
            'payment_method' => $this->payment_method,
        ]);
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

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? '—';
    }

    public function paymentPlanLabel(): string
    {
        return self::PAYMENT_PLANS[$this->payment_plan] ?? '—';
    }

    public function paymentTotalLabel(): string
    {
        return '$'.number_format((float) $this->payment_total, 2);
    }

    public static function homeClassFee(bool $isHomeClass): float
    {
        return $isHomeClass ? self::HOME_CLASS_FEE : 0.0;
    }

    public function discountInput(): string
    {
        return DiscountInput::display((float) $this->discount_percent, (float) $this->discount_amount);
    }
}
