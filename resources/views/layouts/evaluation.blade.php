@extends('layouts.app')

@section('body-class', 'eval-lock-body')

@section('body')
    <div class="eval-shell" @if (! empty($lockEvaluation)) data-eval-lock="1" @endif>
        @if (session('success'))
            <div
                id="app-flash"
                data-type="success"
                data-message="{{ session('success') }}"
                hidden
            ></div>
        @endif

        @yield('content')
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/class-evaluation.js'])
@endpush
