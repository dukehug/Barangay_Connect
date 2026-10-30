<!doctype html>
<html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Barangay Connect | @yield('title', 'Resident Portal')</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
        @if ($__env->hasSection('uses-resident-upload') || $__env->hasSection('uses-resident-forms'))
            @livewireStyles
            @filamentStyles
            @vite('resources/css/resident-upload.css')
        @endif
        <link rel="stylesheet" href="{{ asset('resident/css/style.css') }}" />
        <link rel="stylesheet" href="{{ asset('resident/css/portal.css') }}" />
        <script src="{{ asset('resident/js/modal-icons.js') }}" defer></script>
        @if (request()->routeIs('resident.documents*', 'resident.complaints*', 'resident.login', 'resident.profile'))
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
            <script src="{{ asset('resident/js/resident-alerts.js') }}" defer></script>
        @endif
        <script src="{{ asset('resident/js/activity-data.js') }}?v={{ filemtime(public_path('resident/js/activity-data.js')) }}" defer></script>
        @stack('styles')
        <meta name="csrf-token" content="{{ csrf_token() }}" />
    </head>

    <body class="@yield('body-class')">
        @yield('body')
        @if ($__env->hasSection('uses-resident-upload') || $__env->hasSection('uses-resident-forms'))
            @filamentScripts
            @livewireScripts
        @endif
        @stack('scripts')
    </body>

</html>
