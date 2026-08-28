<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'modelo',
        'año',
        'color',
        'plate',
        'type',
        'status',
        'owner',
        'owner_id',
        'plate_photo_path',
        'circulation_card_path',
        'front_photo_path',
    ];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reservas::class, 'vehicle_id');
    }

    public function typeLabel(): string
    {
        return $this->type === 'automatico' ? 'Automático' : 'Manual';
    }

    public function optionLabel(): string
    {
        return "{$this->modelo} ({$this->plate}) · {$this->typeLabel()}";
    }

    public function documentUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset($path);
    }

    public function frontPhotoUrl(): ?string
    {
        return $this->documentUrl($this->front_photo_path);
    }

    public function platePhotoUrl(): ?string
    {
        return $this->documentUrl($this->plate_photo_path);
    }

    public function circulationCardUrl(): ?string
    {
        return $this->documentUrl($this->circulation_card_path);
    }
}
