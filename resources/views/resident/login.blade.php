@extends('layouts.auth')
@section('title', 'Log In')
@section('intro', 'Log in with your barangay resident account!')

@section('content')
    <form method="POST" action="{{ route('resident.login.store') }}">
        @csrf
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your Email"
            autocomplete="email" required />

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter your password"
            autocomplete="current-password" required />

        <label class="remember-me" for="remember">
            <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember')) />
            <span>Remember me for 7 days</span>
        </label>

        <button type="submit">Log In</button>
    </form>

    <a class="register" href="{{ route('resident.register') }}">Don't have a resident account?</a>
@endsection

@push('scripts')
    <script src="{{ asset('resident/js/login.js') }}" defer></script>
@endpush
