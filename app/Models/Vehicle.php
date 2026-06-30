<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

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
    ];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reservas::class, 'vehicle_id');
    }
}
