<?php

namespace App\Models;

use App\Support\ClassEvaluationCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEvaluation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'reserva_id',
        'vehicle_type',
        'vehicle_label',
        'scores',
        'average',
        'notes',
        'started_at',
        'submitted_at',
        'emailed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'average' => 'float',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reservas::class, 'reserva_id');
    }

    public function vehicleTypeLabel(): string
    {
        return match ($this->vehicle_type) {
            'automatico' => 'Automático',
            'manual' => 'Manual',
            default => $this->vehicle_type ?: '—',
        };
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * @return list<array{id: string, title: string, hint: string, score: int, label: string}>
     */
    public function scoredItems(): array
    {
        $scores = $this->scores ?? [];

        return array_map(function (array $item) use ($scores) {
            $score = (int) ($scores[$item['id']] ?? 0);

            return [
                ...$item,
                'score' => $score,
                'label' => ClassEvaluationCatalog::scoreLabel($score),
            ];
        }, ClassEvaluationCatalog::items());
    }
}
