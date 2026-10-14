@php
    $greeting = \App\Support\DayGreeting::make();
@endphp
<div class="page-greeting">
    <span class="page-greeting__icon is-{{ $greeting->period() }}" aria-hidden="true">
        <i class="bi {{ $greeting->icon() }}"></i>
    </span>
    <div>
        <h1 class="page-title mb-1">{{ $greeting->headline($name ?? '') }}</h1>
        @if (! empty($subtitle))
            <p class="page-subtitle mb-0">{{ $subtitle }}</p>
        @endif
    </div>
</div>
