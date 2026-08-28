<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExtraClass extends Model
{
    public const TYPE_REPOSICION = 'reposicion';

    public const TYPE_EXTRA = 'extra';

    public const TYPE_CORTESIA = 'cortesia';

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        self::TYPE_REPOSICION => 'Reposición',
        self::TYPE_EXTRA => 'Clase extra',
        self::TYPE_CORTESIA => 'Cortesía',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'type',
        'quantity',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
