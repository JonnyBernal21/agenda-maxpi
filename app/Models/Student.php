<?php

namespace App\Models;

use App\Models\Concerns\HasUppercasePersonFields;
use App\Support\DiscountInput;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'created_by',
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
        'notes',
        'is_home_class',
        'meeting_point',
        'meeting_lat',
        'meeting_lng',
        'home_fee_percent',
        'home_fee_amount',
        'payment_subtotal',
        'discount_percent',
        'discount_amount',
        'payment_total',
        'payment_method',
        'payment_plan',
        'payment_initial',
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
            'home_fee_percent' => 'decimal:2',
            'home_fee_amount' => 'decimal:2',
            'payment_subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'payment_total' => 'decimal:2',
            'payment_plan' => 'integer',
            'payment_initial' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function firstActiveClass(): HasOne
    {
        return $this->hasOne(Reservas::class, 'student_id')
            ->where('status', '!=', 'cancelada')
            ->orderBy('date')
            ->orderBy('time');
    }

    public function paidAmount(): float
    {
        if (array_key_exists('paid_amount', $this->attributes)) {
            return round((float) $this->attributes['paid_amount'], 2);
        }

        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('amount'), 2);
        }

        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function balanceDue(): float
    {
        return round(max(0, (float) $this->payment_total - $this->paidAmount()), 2);
    }

    public function isPaidInFull(): bool
    {
        return $this->balanceDue() <= 0;
    }

    /**
     * @return array{date: string, time: string}|null
     */
    public function firstClassWhen(): ?array
    {
        $clase = $this->relationLoaded('firstActiveClass')
            ? $this->firstActiveClass
            : $this->firstActiveClass()->first();

        if (! $clase) {
            return null;
        }

        $date = substr((string) $clase->date, 0, 10);
        $time = Reservas::normalizeTime((string) $clase->time);

        return [
            'date' => \Illuminate\Support\Carbon::parse($date)->format('d/m/Y'),
            'time' => \Illuminate\Support\Carbon::createFromFormat('H:i', $time)?->format('g:i A') ?? $time,
        ];
    }

    public function firstPaymentAmount(): float
    {
        if ((int) $this->payment_plan > 1) {
            return round((float) $this->payment_initial, 2);
        }

        return round((float) $this->payment_total, 2);
    }

    public function recordPayment(?string $paidAt = null): ?StudentPayment
    {
        $amount = $this->firstPaymentAmount();

        if ($amount <= 0) {
            return null;
        }

        return $this->payments()->create([
            'amount' => $amount,
            'paid_at' => $paidAt ?? now()->toDateString(),
            'payment_method' => $this->payment_method,
            'created_by' => auth()->id(),
        ]);
    }

    public function recordInstallment(float $amount, ?string $paymentMethod = null, ?string $paidAt = null): ?StudentPayment
    {
        $amount = round(min($amount, $this->balanceDue()), 2);

        if ($amount <= 0) {
            return null;
        }

        return $this->payments()->create([
            'amount' => $amount,
            'paid_at' => $paidAt ?? now()->toDateString(),
            'payment_method' => $paymentMethod ?: $this->payment_method,
            'created_by' => auth()->id(),
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

    public function calendarHomeProps(): array
    {
        $notes = filled($this->notes) ? (string) $this->notes : null;

        if (! $this->is_home_class) {
            return [
                'isHomeClass' => false,
                'meetingPoint' => null,
                'meetingLat' => null,
                'meetingLng' => null,
                'notes' => $notes,
            ];
        }

        return [
            'isHomeClass' => true,
            'meetingPoint' => filled($this->meeting_point) ? (string) $this->meeting_point : null,
            'meetingLat' => $this->meeting_lat !== null ? (float) $this->meeting_lat : null,
            'meetingLng' => $this->meeting_lng !== null ? (float) $this->meeting_lng : null,
            'notes' => $notes,
        ];
    }

    public function homeFeeInput(): string
    {
        if (! $this->is_home_class) {
            return '';
        }

        return DiscountInput::display((float) $this->home_fee_percent, (float) $this->home_fee_amount);
    }

    public function discountInput(): string
    {
        return DiscountInput::display((float) $this->discount_percent, (float) $this->discount_amount);
    }
}
