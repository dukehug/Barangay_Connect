@extends('layouts.resident')
@section('title', 'Announcements')
@section('content')
    <section class="greeting page-head">
        <div>
            <h2>Announcements</h2>
            <p>Stay updated with the latest barangay news.</p>
        </div>
    </section>
    <div class="announce-filter">
        <nav class="toolbar" aria-label="Announcement categories">
            <a @class(['chip', 'selected' => !request('category')]) href="{{ route('resident.announcements') }}">All</a>
            @foreach ($categories as $category)
                <a @class(['chip', 'selected' => request('category') === $category])
                    href="{{ route('resident.announcements', ['category' => $category]) }}">{{ $category }}</a>
            @endforeach
        </nav>
        <div class="announce-list">
            @forelse ($announcements as $announcement)
                <a class="announce-card" href="{{ route('resident.announcement', $announcement) }}">
                    <img src="{{ $announcement->getFirstMediaUrl('cover') }}" alt="" loading="lazy">
                    <div class="announce-body">
                        <h3>{{ $announcement->title }}</h3>
                        <div class="announce-meta">
                            <span>{{ $announcement->posted_at->format('F j, Y') }}</span>
                            <span class="badge">{{ $announcement->category }}</span>
                        </div>
                        <p>{{ Str::limit(strip_tags($announcement->content), 240) }}</p>
                        <span class="read-more">Read
                            Announcement →</span>
                    </div>
                </a>
            @empty
                <p class="panel">No announcements available.</p>
            @endforelse
        </div>
    </div>
    @include('resident.pagination', ['paginator' => $announcements])
@endsection
