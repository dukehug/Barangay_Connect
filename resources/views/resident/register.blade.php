@extends('layouts.auth')
@section('title', 'Register')
@section('intro', 'Create your barangay resident account!')
@section('card-class', 'register-card')
@section('content')
    <form class="resident-details-form" method="POST" action="{{ route('resident.register.store') }}">
        @csrf
        <div class="resident-section-grid">
            <section class="resident-form-section" aria-labelledby="registerPersonalTitle">
                <header class="resident-section-heading">
                    <h2 id="registerPersonalTitle">Personal information</h2>
                    <p>Tell us a little about yourself.</p>
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
                        <h2 id="registerAddressTitle">Residential address</h2>
                        <p>Your current address in the barangay.</p>
                    </header>
                    <div class="resident-field">
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="resident_address" value="{{ old('resident_address') }}"
                            placeholder="House No., Street, Barangay" autocomplete="street-address" required />
                    </div>
                </section>
                <section class="resident-form-section" aria-labelledby="registerAccountTitle">
                    <header class="resident-section-heading">
                        <h2 id="registerAccountTitle">Account details</h2>
                        <p>Use these details to securely sign in.</p>
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
        <script>
            alert('Registration successful! Please log in with your account.');
            window.location.href = @json(route('resident.login'));
        </script>
    @endif
@endpush
