<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory, SoftDeletes;

    public const CATEGORY_COMBUSTIBLE = 'combustible';

    public const CATEGORY_MANTENIMIENTO = 'mantenimiento';

    public const CATEGORY_SEGURO = 'seguro';

    public const CATEGORY_NOMINA = 'nomina';

    public const CATEGORY_RENTA = 'renta';

    public const CATEGORY_SERVICIOS = 'servicios';

    public const CATEGORY_PAPELERIA = 'papeleria';

    public const CATEGORY_PUBLICIDAD = 'publicidad';

    public const CATEGORY_OTROS = 'otros';

    public const PAYMENT_CASH = 'efectivo';

    public const PAYMENT_TRANSFER = 'transferencia';

    public const PAYMENT_CARD = 'tarjeta';

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        self::CATEGORY_COMBUSTIBLE => 'Combustible',
        self::CATEGORY_MANTENIMIENTO => 'Mantenimiento',
        self::CATEGORY_SEGURO => 'Seguro',
        self::CATEGORY_NOMINA => 'Nómina',
        self::CATEGORY_RENTA => 'Renta',
        self::CATEGORY_SERVICIOS => 'Servicios',
        self::CATEGORY_PAPELERIA => 'Papelería',
        self::CATEGORY_PUBLICIDAD => 'Publicidad',
        self::CATEGORY_OTROS => 'Otros',
    ];

    /**
     * @var array<string, string>
     */
    public const PAYMENT_METHODS = [
        self::PAYMENT_CASH => 'Efectivo',
        self::PAYMENT_TRANSFER => 'Transferencia',
        self::PAYMENT_CARD => 'Tarjeta',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'concept',
        'category',
        'amount',
        'payment_method',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function amountLabel(): string
    {
        return '$'.number_format((float) $this->amount, 2);
    }

    public function dateLabel(): string
    {
        return $this->date?->format('d/m/Y') ?? '—';
    }
}
