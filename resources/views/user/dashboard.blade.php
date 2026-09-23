@extends('layouts.user')

@section('title', 'Dashboard')

@section('content')

    <div class="mb-4">
        <h2 class="mb-1">
            Welcome, {{ auth()->user()->name }}
        </h2>
        <p class="text-muted mb-0">
            This is your dashboard.
        </p>
    </div>

    {{-- Add simple content here later, e.g. their own stock requests, profile info, etc. --}}

@endsection