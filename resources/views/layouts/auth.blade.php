@extends('layouts.app')
@section('body-class', 'login-page')

@section('body')
    <main class="login-card @yield('card-class')">
        <img class="logo" src="{{ asset('resident/images/brgy.png') }}" alt="Barangay Connect logo" />
        <h1>Barangay Connect</h1>
        <p class="tagline">A Barangay Hub Service</p>
        <p class="intro">@yield('intro')</p>
        @include('resident.messages')
        @yield('content')
    </main>
@endsection
