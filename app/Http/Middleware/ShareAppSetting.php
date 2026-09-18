<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareAppSetting
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $setting = app(SettingService::class)->current();
            config(['app.name' => $setting->companyName()]);
            config(['app.timezone' => $setting->timezone()]);
            date_default_timezone_set($setting->timezone());
            view()->share('appSetting', $setting);
        } catch (\Throwable) {
            view()->share('appSetting', null);
        }

        return $next($request);
    }
}
