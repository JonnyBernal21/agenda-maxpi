<?php

namespace App\Models;

use App\Support\SchoolProfile;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'logo_path',
        'timezone',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'currency',
        'home_class_fee',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'home_class_fee' => 'decimal:2',
        ];
    }

    public function companyName(): string
    {
        return filled($this->company_name) ? $this->company_name : 'Agenda MaxPi';
    }

    public function logoUrl(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        return asset($this->logo_path);
    }

    public function timezone(): string
    {
        $value = $this->attributes['timezone'] ?? null;

        return filled($value) ? $value : SchoolProfile::DEFAULT_TIMEZONE;
    }

    public function currency(): string
    {
        $value = $this->attributes['currency'] ?? null;

        return filled($value) ? $value : SchoolProfile::DEFAULT_CURRENCY;
    }

    public function countryName(): string
    {
        $value = $this->attributes['country'] ?? null;

        return filled($value) ? $value : SchoolProfile::DEFAULT_COUNTRY;
    }

    public function homeClassFee(): float
    {
        $value = $this->attributes['home_class_fee'] ?? null;

        if (! is_numeric($value)) {
            return SchoolProfile::DEFAULT_HOME_CLASS_FEE;
        }

        return round(max(0, (float) $value), 2);
    }
}
