<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingService
{
    public const CACHE_KEY = 'app_setting';

    public function current(): Setting
    {
        if (! Schema::hasTable('settings')) {
            return new Setting(['company_name' => 'Agenda MaxPi']);
        }

        $setting = Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()->first() ?? Setting::query()->create([
                'company_name' => 'Agenda MaxPi',
            ]);
        });

        if (! array_key_exists('timezone', $setting->getAttributes()) && Schema::hasColumn('settings', 'timezone')) {
            $this->forget();

            return $this->current();
        }

        return $setting;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
