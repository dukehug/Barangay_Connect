@extends('layouts.app')
@section('body-class')
    dash-page @yield('page-class')
@endsection

{{--  side bar navigation --}}
@section('body')
    <aside>
        <div class="brand">
            <img src="{{ asset('resident/images/brgy.png') }}" alt="Barangay Connect logo" />
            Barangay Connect
        </div>
        {{-- Separate the brand from the primary navigation. --}}
        <hr class="nav-divider" />
        <nav aria-label="Primary navigation">
            <a data-nav="dashboard" @class(['active' => request()->routeIs('resident.dashboard*')]) href="{{ route('resident.dashboard') }}">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-house" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                    <path
                        d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                </svg>
                <span class="nav-item-label">Dashboard</span>
            </a>
            <a data-nav="announcements" @class(['active' => request()->routeIs('resident.announcement*')]) href="{{ route('resident.announcements') }}">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-megaphone" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                    <path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14" />
                    <path d="M8 6v8" />
                </svg>
                <span class="nav-item-label">Announcements</span>
            </a>
            <a data-nav="documents" @class(['active' => request()->routeIs('resident.documents*')]) href="{{ route('resident.documents') }}">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-file-text" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
                    <path d="M14 2v5a1 1 0 0 0 1 1h5" />
                    <path d="M10 9H8" />
                    <path d="M16 13H8" />
                    <path d="M16 17H8" />
                </svg>
                <span class="nav-item-label">My Documents</span>
            </a>
            <a data-nav="complaints" @class(['active' => request()->routeIs('resident.complaints*')]) href="{{ route('resident.complaints') }}">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-message-square-warning"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z" />
                    <path d="M12 15h.01" />
                    <path d="M12 7v4" />
                </svg>
                <span class="nav-item-label">My Complaints</span>
            </a>
            <a data-nav="activity" @class(['active' => request()->routeIs('resident.activity*')]) href="{{ route('resident.activity') }}">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-file-text" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 3H5v18h14V3h-3M9 3h6v4H9zM8 12h8M8 16h5" />

                </svg>
                <span class="nav-item-label">My Activity</span>
            </a>
        </nav>

        {{--  resident profile --}}
        <div class="user">
            <a class="user-profile" href="{{ route('resident.profile') }}" aria-label="View your profile">
                <span class="avatar" aria-hidden="true">
                    <img src="{{ auth('resident')->user()->profile_image_url ? route('resident.profile.photo') : asset('resident/images/profile-placeholder.svg') }}"
                        alt="" />
                </span>
                <span class="user-details">
                    <span class="user-name">{{ auth('resident')->user()->first_name }}</span>
                </span>
            </a>
            <form class="user-logout" method="POST" action="{{ route('resident.logout') }}">
                @csrf
                <button type="submit" aria-label="Log out" title="Log out">
                    <svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
                    </svg>
                </button>
            </form>
        </div>

    </aside>

    <main class="@yield('main-class')">
        @include('resident.messages')
        @yield('content')
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('resident/js/sidebar.js') }}" defer></script>
@endpush
