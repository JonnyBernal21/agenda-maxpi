@push('styles')
    <link rel="preload" as="image" href="{{ url('curso-bg.jpg') }}">
    <style>
        .auth-wrapper {
            background-color: #111;
            background-image: url('{{ url('curso-bg.jpg') }}');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }
    </style>
@endpush
