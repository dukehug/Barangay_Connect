@extends('layouts.resident')
@section('title', 'Dashboard')
@section('content')

    {{-- Page heading and introductory message. --}}
    <section class="greeting">
        <h2 id="greet" data-name="{{ auth('resident')->user()->first_name }}">Welcome,
            {{ auth('resident')->user()->first_name }}!</h2>
        <p>What can we help you with today?</p>
    </section>

    {{-- Shortcuts to resident services. --}}
    <section class="services">
        <article class="card">
            <div class="icon green">
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
            </div>
            <h4>Document Request</h4>
            <p>Request Official<br />Barangay Documents</p>
            <a class="btn" href="{{ route('resident.documents') }}">Request Document
                <svg aria-hidden="true" focusable="false" class="lucide lucide-arrow-right"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14" />
                    <path d="m12 5 7 7-7 7" />
                </svg>
            </a>
        </article>

        <article class="card">
            <div class="icon yellow">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-message-square-warning"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z" />
                    <path d="M12 15h.01" />
                    <path d="M12 7v4" />
                </svg>
            </div>
            <h4>Complaint</h4>
            <p>
                File complaints about barangay<br />services or issues in your area
            </p>
            <a class="btn" href="{{ route('resident.complaints') }}">File Complaints
                <svg aria-hidden="true" focusable="false" class="lucide lucide-arrow-right"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14" />
                    <path d="m12 5 7 7-7 7" />
                </svg>
            </a>
        </article>
    </section>

    {{-- Preview the latest barangay announcements. --}}
    <section class="panel announcements-preview" id="announcements">
        <div class="panel-head">
            <div class="icon red">
                <svg aria-hidden="true" focusable="false" class="lucide lucide-megaphone" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                    <path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14" />
                    <path d="M8 6v8" />
                </svg>
            </div>
            <h3>Announcements</h3>
            <a class="view-all btn" href="{{ route('resident.announcements') }}">View all announcements
                <svg aria-hidden="true" focusable="false" class="lucide lucide-arrow-right"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14" />
                    <path d="m12 5 7 7-7 7" />
                </svg>
            </a>
        </div>
        <ul>
            @forelse ($announcements as $announcement)
                <li>
                    <div>
                        <h4>
                            <a href="{{ route('resident.announcement', $announcement) }}">{{ $announcement->title }}</a>
                        </h4>
                        <p class="date">{{ $announcement->posted_at?->format('M d, Y') }}</p>
                        <p>{{ Str::limit(strip_tags($announcement->content), 180) }}</p>
                    </div>
            </li>@empty
                <li>No announcements yet.</li>
            @endforelse
        </ul>
    </section>

@endsection

@push('scripts')
    <script src="{{ asset('resident/js/dashboard.js') }}" defer></script>
@endpush
