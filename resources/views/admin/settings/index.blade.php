@extends('layouts.admin')

@section('title', 'Ajustes — ' . config('app.name'))

@section('content')
    @include('admin.partials.settings-shell', [
        'tab' => 'ajustes',
        'slot' => view('admin.settings.partials.brand-form', compact('setting'))->render(),
    ])
@endsection
