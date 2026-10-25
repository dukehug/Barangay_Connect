@extends('layouts.auth')
@section('title', 'Register')
@section('intro', 'Create your barangay resident account!')
@section('card-class', 'register-card')
@section('uses-resident-forms', true)
@section('content')
    <form class="resident-details-form" method="POST" action="{{ route('resident.register.store') }}">
        @csrf
        <div class="resident-section-grid">
            <section class="resident-form-section" aria-labelledby="registerPersonalTitle">
                <header class="resident-section-heading">
                    <svg class="resident-section-icon" width="24" height="24" focusable="false" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"></path>
                    </svg>
                    <h2 id="registerPersonalTitle">Personal information</h2>
                </header>
                <div class="resident-field-grid resident-name-grid">
                    <div>
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="first_name" value="{{ old('first_name') }}"
                            placeholder="Enter your first name" autocomplete="given-name" required />
                    </div>
                    <div>
                        <label for="middleName">Middle Name:</label>
                        <input type="text" id="middleName" name="middle_name" value="{{ old('middle_name') }}"
                            placeholder="Enter your middle name" autocomplete="additional-name" />
                    </div>
                    <div>
                        <label for="lastName">Last Name:</label>
                        <input type="text" id="lastName" name="last_name" value="{{ old('last_name') }}"
                            placeholder="Enter your last name" autocomplete="family-name" required />
                    </div>
                </div>
                <div class="resident-field-grid">
                    <div class="resident-field">
                        <label for="birthdate">Birthdate:</label>
                        <input type="date" id="birthdate" name="birth_date" value="{{ old('birth_date') }}"
                            autocomplete="bday" max="{{ date('Y-m-d') }}" required />
                    </div>
                    <div class="resident-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                            <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                        </select>
                    </div>
                </div>
                <div class="resident-field-grid">
                    <div>
                        <label for="bloodType">Blood Type:</label>
                        <select id="bloodType" name="blood_type">
                            <option value="" @selected(!old('blood_type'))>Not specified</option>
                            <option @selected(old('blood_type') === 'A+')>A+</option>
                            <option @selected(old('blood_type') === 'A-')>A-</option>
                            <option @selected(old('blood_type') === 'B+')>B+</option>
                            <option @selected(old('blood_type') === 'B-')>B-</option>
                            <option @selected(old('blood_type') === 'AB+')>AB+</option>
                            <option @selected(old('blood_type') === 'AB-')>AB-</option>
                            <option @selected(old('blood_type') === 'O+')>O+</option>
                            <option @selected(old('blood_type') === 'O-')>O-</option>
                        </select>
                    </div>
                    <div>
                        <label for="maritalStatus">Marital Status:</label>
                        <select id="maritalStatus" name="marital_status" required>
                            <option value="" disabled @selected(!old('marital_status'))>Select marital status</option>
                            <option @selected(old('marital_status') === 'Single')>Single</option>
                            <option @selected(old('marital_status') === 'Married')>Married</option>
                            <option @selected(old('marital_status') === 'Widowed')>Widowed</option>
                            <option @selected(old('marital_status') === 'Divorced')>Divorced</option>
                        </select>
                    </div>
                </div>
            </section>
            <div class="resident-section-stack">
                <section class="resident-form-section" aria-labelledby="registerAddressTitle">
                    <header class="resident-section-heading">
                        <svg class="resident-section-icon" width="24" height="24" focusable="false" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"></path>
                        </svg>
                        <h2 id="registerAddressTitle">Residential address</h2>
                    </header>
                    <div class="resident-field">
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="resident_address" value="{{ old('resident_address') }}"
                            placeholder="House No., Street, Barangay" autocomplete="street-address" required />
                    </div>
                </section>
                <section class="resident-form-section" aria-labelledby="registerAccountTitle">
                    <header class="resident-section-heading">
                        <svg class="resident-section-icon" width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M125.4 128C91.5 128 64 155.5 64 189.4C64 190.3 64 191.1 64.1 192L64 192L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 192L575.9 192C575.9 191.1 576 190.3 576 189.4C576 155.5 548.5 128 514.6 128L125.4 128zM528 256.3L528 448C528 456.8 520.8 464 512 464L128 464C119.2 464 112 456.8 112 448L112 256.3L266.8 373.7C298.2 397.6 341.7 397.6 373.2 373.7L528 256.3zM112 189.4C112 182 118 176 125.4 176L514.6 176C522 176 528 182 528 189.4C528 193.6 526 197.6 522.7 200.1L344.2 335.5C329.9 346.3 310.1 346.3 295.8 335.5L117.3 200.1C114 197.6 112 193.6 112 189.4z"/></svg>
                        <h2 id="registerAccountTitle">Account details</h2>
                    </header>
                    <div class="resident-field">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            placeholder="Enter your email" autocomplete="email" required />
                    </div>
                    <div class="resident-field-grid">
                        <div class="resident-field">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" minlength="8" placeholder="Enter your password"
                                autocomplete="new-password" required />
                        </div>
                        <div class="resident-field">
                            <label for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>
                    <p class="resident-field-hint">Choose a password with at least 8 characters.</p>
                </section>
            </div>
        </div>
        <footer class="resident-form-actions">
            <a class="resident-secondary-action" href="{{ route('resident.login') }}">Already have an account? Log In</a>
            <button type="submit">Create account</button>
        </footer>
    </form>
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/register.js') }}" defer></script>

    @if (session('registered'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Registration Successful',
                text: 'Please log in with your account.',
                confirmButtonText: 'Go to Login',
                confirmButtonColor: '#3b82f6',
                allowOutsideClick: false,
                timer: 5000,
                timerProgressBar: true
            }).then(() => {
                window.location.href = @json(route('resident.login'));
            });
        </script>
    @endif
@endpush
