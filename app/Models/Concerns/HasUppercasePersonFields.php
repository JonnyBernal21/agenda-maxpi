<?php

namespace App\Models\Concerns;

trait HasUppercasePersonFields
{
    /** @var list<string> */
    protected array $uppercaseFields = [
        'name',
        'last_name',
        'address',
        'city',
        'state',
    ];

    public static function bootHasUppercasePersonFields(): void
    {
        static::saving(function ($model): void {
            foreach ($model->uppercaseFields as $field) {
                $value = $model->getAttribute($field);

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $model->setAttribute($field, mb_strtoupper($value, 'UTF-8'));
            }
        });
    }
}
