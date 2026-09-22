<?php

namespace App\Models;

use Database\Factories\StudentPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPayment extends Model
{
    /** @use HasFactory<StudentPaymentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'created_by',
        'amount',
        'paid_at',
        'payment_method',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amountLabel(): string
    {
        return '$'.number_format((float) $this->amount, 2);
    }

    public function paymentMethodLabel(): string
    {
        if (! $this->payment_method) {
            return '—';
        }

        return Student::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function recordedAtLabel(): string
    {
        return $this->created_at?->timezone(config('app.timezone'))->format('d/m/Y g:i A') ?? '—';
    }

    public function isFullPayment(): bool
    {
        $isFirst = (bool) $this->getAttribute('is_first_payment');
        $total = round((float) ($this->student?->payment_total ?? 0), 2);

        return $isFirst && $total > 0 && round((float) $this->amount, 2) >= $total;
    }

    public function saleTypeLabel(): string
    {
        return $this->isFullPayment() ? 'Pago completo' : 'Abono';
    }

    public function conceptLabel(): string
    {
        $course = $this->student?->course?->name;

        return $course
            ? $this->saleTypeLabel().' · '.$course
            : $this->saleTypeLabel();
    }

    public function creatorName(): string
    {
        return $this->creator?->name ?: '—';
    }

    public function studentCreatorName(): string
    {
        return $this->student?->creator?->name ?: '—';
    }
}
