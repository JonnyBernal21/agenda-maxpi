<?php

namespace App\Models;

use App\Support\AppTheme;
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
        'theme',
        'timezone',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'currency',
    ];

    public function companyName(): string
    {
        return filled($this->company_name) ? $this->company_name : 'Agenda MaxPi';
    }

    public function logoUrl(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        return '/'.ltrim(str_replace('\\', '/', $this->logo_path), '/');
    }

    public function faviconUrl(): string
    {
        return $this->logoUrl() ?: '/logo.png';
    }

    public function theme(): string
    {
        $value = $this->attributes['theme'] ?? null;

        return AppTheme::isValid($value) ? $value : AppTheme::DEFAULT;
    }

    /**
     * @return array<string, string>
     */
    public function themeTokens(): array
    {
        return AppTheme::tokens($this->theme());
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
}
