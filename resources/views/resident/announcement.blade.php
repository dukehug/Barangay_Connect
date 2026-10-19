@extends('layouts.resident')
@section('title', $announcement->title)
@section('main-class', 'detail-main')
@section('page-class', 'announcement-detail-page')
@section('content')
    <nav class="detail-breadcrumb" aria-label="Breadcrumb">
        <ol>
            <li>
                <a href="{{ route('resident.announcements') }}">Announcements</a>
            </li>
            <li>
                <span class="breadcrumb-separator" aria-hidden="true">/</span>
                <span aria-current="page">Announcement
                    Details</span>
            </li>
        </ol>
    </nav>
    <article class="detail-article" aria-labelledby="announcementTitle">
        <h1 id="announcementTitle">{{ $announcement->title }}</h1>
        <dl class="detail-metadata">
            <div>
                <dt>Publisher</dt>
                <dd>Barangay Office</dd>
            </div>
            <div>
                <dt>Publish Date</dt>
                <dd>
                    <time
                        datetime="{{ $announcement->posted_at->toIso8601String() }}">{{ $announcement->posted_at->format('F j, Y') }}</time>
                </dd>
            </div>
            <div>
                <dt>Announcement Category</dt>
                <dd>{{ $announcement->category }}</dd>
            </div>
        </dl>
        <div @class([
            'announcement-content',
            'announcement-content-plain' =>
                $announcement->content === strip_tags($announcement->content),
        ])>
            {!! str($announcement->content)->sanitizeHtml() !!}
        </div>
    </article>
    <nav class="detail-pagination" aria-label="Announcement navigation">
        @if ($previousAnnouncement)
            <a class="detail-button" rel="prev" href="{{ route('resident.announcement', $previousAnnouncement) }}">←
            Previous Announcement</a>@else<span class="detail-button" aria-disabled="true">← Previous
                Announcement</span>
        @endif
        @if ($nextAnnouncement)
            <a class="detail-button" rel="next" href="{{ route('resident.announcement', $nextAnnouncement) }}">Next
            Announcement →</a>@else<span class="detail-button" aria-disabled="true">Next Announcement →</span>
        @endif
    </nav>
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/announcement.js') }}" defer></script>
@endpush
