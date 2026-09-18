@php
    $setting = $appSetting ?? null;
    $company = $setting?->companyName() ?? config('app.name', 'Agenda MaxPi');
@endphp
@if ($setting?->logoUrl())
    <img src="{{ $setting->logoUrl() }}" alt="" class="navbar-brand-logo">
@else
    <span class="navbar-brand-mark"><i class="{{ $icon ?? 'bi bi-calendar2-week' }}"></i></span>
@endif
<span class="navbar-brand-name">{{ $company }}</span>
