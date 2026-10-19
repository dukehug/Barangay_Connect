@extends('layouts.resident')
@section('title', 'My Profile')
@section('main-class', '')
@section('content')

    {{-- Page heading and introductory message. --}}
    <section class="greeting">
        <h2>My Profile</h2>
        <p>View and manage your personal details.</p>
    </section>

    <div class="profile-grid">
        {{-- Left: profile summary --}}
        <div class="panel profile-card">
            <label class="avatar-upload" title="Change profile photo">
                <img id="profilePhoto" data-placeholder="{{ asset('resident/images/profile-placeholder.svg') }}"
                    class="profile-avatar"
                    src="{{ $resident->profile_image_url ? route('resident.profile.photo') : asset('resident/images/profile-placeholder.svg') }}"
                    alt="Profile photo" />
                <span class="avatar-edit">Change photo</span>
                <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" class="photo-file-input" />
            </label>
            <p id="photoMessage" class="photo-message" role="status">
            </p>
            <h3 class="profile-name">{{ $resident->full_name }}</h3>
            <span class="profile-badge">Barangay Resident</span>
            <p class="profile-meta">Date Registered: {{ $resident->created_at->format('F j, Y') }}</p>

            <div class="profile-actions">
                <a class="btn btn-block profile-activity-link" href="{{ route('resident.activity') }}">My Activity</a>
                <a class="btn btn-block" href="#profileEdit" data-open-dialog="profileEdit">Edit Profile</a>
                <form method="POST" action="{{ route('resident.logout') }}">
                    @csrf
                    <button class="btn btn-block btn-red" type="submit">Log out</button>
                </form>
            </div>
        </div>

        {{-- Right: details --}}
        <div class="profile-details">
            <section class="panel info-section">
                <div class="panel-head">
                    <div class="icon navy">
                        <svg aria-hidden="true" focusable="false" class="lucide lucide-user-round"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="8" r="5" />
                            <path d="M20 21a8 8 0 0 0-16 0" />
                        </svg>
                    </div>
                    <h3>Personal Information</h3>
                </div>
                {{-- Resident information displayed as label and value pairs. --}}
                <dl class="info-list">
                    <div class="info-row">
                        <dt>First Name</dt>
                        <dd>{{ $resident->first_name }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Last Name</dt>
                        <dd>{{ $resident->last_name }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Birthdate</dt>
                        <dd>{{ $resident->birth_date->format('F j, Y') }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Residential Address</dt>
                        <dd>{{ $resident->resident_address }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Blood Type</dt>
                        <dd>{{ $resident->blood_type ?: 'Not specified' }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Marital Status</dt>
                        <dd>{{ $resident->marital_status }}</dd>
                    </div>
                </dl>
            </section>

            <section class="panel info-section">
                <div class="panel-head">
                    <div class="icon green">
                        <svg aria-hidden="true" focusable="false" class="lucide lucide-phone"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />
                        </svg>
                    </div>
                    <h3>Contact Information</h3>
                </div>
                {{-- Resident information displayed as label and value pairs. --}}
                <dl class="info-list">
                    <div class="info-row">
                        <dt>Email</dt>
                        <dd>{{ $resident->email }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Phone Number</dt>
                        <dd>{{ $resident->contact_number ?: 'Not specified' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
    <dialog id="photoEditor" class="photo-editor" aria-labelledby="photoEditorTitle" aria-describedby="photoEditorHelp">
        <div class="photo-editor-heading">
            <h2 id="photoEditorTitle">Edit profile photo</h2>
            <button type="button" id="closePhotoEditor" aria-label="Cancel photo editing">×</button>
        </div>
        <p id="photoEditorHelp">Drag to move your photo. Use the slider to zoom, or focus the preview and use arrow keys to
            move.</p>
        <div class="photo-crop-area">
            <canvas id="photoCanvas" width="300" height="300" tabindex="0"
                aria-label="Photo crop preview. Use arrow keys to move the photo.">
            </canvas>
            <span class="photo-crop-guide" aria-hidden="true">
            </span>
        </div>
        <label for="photoZoom">Zoom <output id="photoZoomValue" for="photoZoom">100%</output>
        </label>
        <input id="photoZoom" type="range" min="1" max="4" step="0.01" value="1" />
        <p id="photoEditorError" class="photo-message" role="alert">
        </p>
        <div class="photo-editor-actions">
            <button type="button" id="resetPhoto">Reset</button>
            <button type="button" id="cancelPhoto">Cancel</button>
            <button type="button" id="savePhoto">Save photo</button>
        </div>
    </dialog>
    <footer class="profile-version">Barangay Connect · v1.0.5</footer>

    <dialog id="profileEdit" class="request-dialog" aria-labelledby="profileEditTitle"
        @if ($errors->any()) data-reopen @endif>
        <header class="request-dialog-header">
            <h2 id="profileEditTitle">Edit Profile</h2>
            <button type="button" data-close-dialog aria-label="Close profile editor">×</button>
        </header>
        @if ($errors->any())
            @include('resident.messages')
        @endif
        <form id="profileForm" class="portal-form" method="POST" enctype="multipart/form-data"
            action="{{ route('resident.profile.update') }}">
            @csrf
            @method('PATCH')

            @foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'contact_number' => 'Contact number', 'resident_address' => 'Address'] as $field => $label)
                <label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}"
                    type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $resident->$field) }}"
                    @required($field !== 'contact_number')>
            @endforeach
            <label for="birth_date">Birth date</label>
            <input type="date" id="birth_date" name="birth_date" required
                value="{{ old('birth_date', $resident->birth_date->format('Y-m-d')) }}">
            @foreach (['gender' => ['Male', 'Female'], 'marital_status' => ['Single', 'Married', 'Widowed', 'Divorced'], 'blood_type' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']] as $field => $options)
                <label for="{{ $field }}">{{ Str::headline($field) }}</label>
                <select id="{{ $field }}" name="{{ $field }}">
                    @if ($field === 'blood_type')
                        <option value="">Not specified</option>
                        @endif @foreach ($options as $option)
                            <option value="{{ $option }}" @selected(old($field, $resident->$field) === $option)>{{ $option }}</option>
                        @endforeach
                </select>
            @endforeach
            <label for="photo">Profile photo (JPG / PNG / WebP, up to 5 MB)</label>
            <input type="file" name="photo" id="photo" data-cropped-photo
                accept="image/jpeg,image/png,image/webp">
            <button class="btn" type="submit">Save profile</button>
        </form>
    </dialog>
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/profile.js') }}" defer></script>
@endpush
