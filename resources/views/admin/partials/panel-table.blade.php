<div class="panel-card">
    <div class="panel-card__header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-start gap-3">
                <div class="stat-card__icon mb-0 mt-1">
                    <i class="bi {{ $icon }}"></i>
                </div>
                <div>
                    <h2 class="h5 fw-semibold mb-1">{{ $title }}</h2>
                    @isset($subtitle)
                        <p class="text-muted small mb-0">{{ $subtitle }}</p>
                    @endisset
                </div>
            </div>
            @isset($action)
                <div>{!! $action !!}</div>
            @endisset
        </div>
    </div>
    <div class="panel-card__body p-0">
        <div class="table-responsive p-3 pt-0">
            {!! $table !!}
        </div>
    </div>
</div>
