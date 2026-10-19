@extends('layouts.auth')
@section('title', 'Register')
@section('intro', 'Create your barangay resident account!')
@section('card-class', 'register-card')

@section('content')
    <form method="POST" action="{{ route('resident.register.store') }}">
        @csrf
        <div class="row">
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

        <label for="birthdate">Birthdate:</label>
        <input type="date" id="birthdate" 
                name="birth_date" 
                value="{{ old('birth_date') }}" 
                autocomplete="bday"  
                max="{{ date('Y-m-d') }}"  
                required />

        <label for="address">Address:</label>
        <input type="text" id="address" name="resident_address" value="{{ old('resident_address') }}"
            placeholder="House No., Street, Barangay" autocomplete="street-address" required />

        <div class="row">
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

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <option value="Male" @selected(old('gender') === 'Male')>Male</option>
            <option value="Female" @selected(old('gender') === 'Female')>Female</option>
        </select>

        <label for="email">Email:</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="{{ old('email') }}" 
               placeholder="Enter your Email"
               autocomplete="email" 
               required />

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" minlength="8" placeholder="Enter your password"
            autocomplete="new-password" required />

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        <button type="submit">Register</button>
    </form>

    <a class="register" href="{{ route('resident.login') }}">Already have a resident account? Log In</a>
@endsection

@push('scripts')
    <script src="{{ asset('resident/js/register.js') }}" defer></script>
@endpush
