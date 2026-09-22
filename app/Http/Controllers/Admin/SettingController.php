<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use App\Support\SchoolProfile;
use App\Support\UploadedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'setting' => $this->settings->current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        UploadedDocument::assertValid($request, ['logo' => 'El logo']);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:80'],
            'logo' => ['nullable', 'file', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'timezone' => ['required', 'string', Rule::in(array_keys(SchoolProfile::TIMEZONES))],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'currency' => ['required', 'string', Rule::in(array_keys(SchoolProfile::CURRENCIES))],
        ]);

        $setting = Setting::query()->first() ?? new Setting();
        $logoPath = $setting->logo_path;

        if ($request->boolean('remove_logo') && ! $request->file('logo')) {
            $this->deleteLogo($logoPath);
            $logoPath = null;
        }

        if ($request->file('logo') instanceof UploadedFile) {
            $this->deleteLogo($logoPath);
            $logoPath = $this->storeLogo($request->file('logo'));
        }

        $setting->company_name = $validated['company_name'];
        $setting->logo_path = $logoPath;
        $setting->timezone = $validated['timezone'];
        $setting->phone = $validated['phone'] ?? null;
        $setting->email = $validated['email'] ?? null;
        $setting->address = $validated['address'] ?? null;
        $setting->city = $validated['city'] ?? null;
        $setting->state = $validated['state'] ?? null;
        $setting->zip = $validated['zip'] ?? null;
        $setting->country = $validated['country'] ?? SchoolProfile::DEFAULT_COUNTRY;
        $setting->currency = $validated['currency'];
        $setting->save();

        $this->settings->forget();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Se guardaron los ajustes de la empresa.');
    }

    private function storeLogo(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        $filename = 'logo_'.Str::uuid().'.'.$extension;

        return 'uploads/'.$file->storeAs('settings', $filename, 'uploads');
    }

    private function deleteLogo(?string $path): void
    {
        if (! $path) {
            return;
        }

        $relative = ltrim(Str::after($path, 'uploads/'), '/');

        if ($relative !== '' && Storage::disk('uploads')->exists($relative)) {
            Storage::disk('uploads')->delete($relative);
        }
    }
}
