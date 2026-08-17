@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" class="instructor-doc-link">{{ $label }}</a>
@else
    <span class="text-muted small">{{ $label }}</span>
@endif
