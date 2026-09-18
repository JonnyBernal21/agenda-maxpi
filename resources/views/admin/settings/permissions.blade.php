@extends('layouts.admin')

@section('title', 'Permisos por rol — ' . config('app.name'))

@section('content')
    @include('admin.partials.settings-shell', [
        'tab' => 'permisos',
        'slot' => view('admin.settings.partials.permissions-board', [
            'roles' => $roles,
            'selected' => $selected,
            'groups' => $groups,
            'selectedKeys' => $selectedKeys,
        ])->render(),
    ])
@endsection
