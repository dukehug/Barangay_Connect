@if (request()->routeIs('resident.documents*', 'resident.complaints*'))
    @php
        $notice = match (true) {
            $errors->any() => ['icon' => 'error', 'title' => 'Please check your submission', 'message' => implode("\n", $errors->all())],
            (bool) session('complaint_submitted') => ['icon' => 'success', 'title' => 'Submission Successful', 'message' => 'Your complaint has been submitted successfully.'],
            (bool) session('document_submitted') => ['icon' => 'success', 'title' => 'Submission Successful', 'message' => 'Your document has been submitted successfully.'],
            (bool) session('document_responded') => ['icon' => 'success', 'title' => 'Response Sent', 'message' => 'Your response has been sent successfully.'],
            (bool) session('status') => ['icon' => 'success', 'title' => 'Done', 'message' => session('status')],
            default => null,
        };
    @endphp
    @if ($notice)
        <p class="portal-message" role="alert" data-resident-alert data-icon="{{ $notice['icon'] }}"
            data-title="{{ $notice['title'] }}" data-message="{{ $notice['message'] }}">{{ $notice['message'] }}</p>
    @endif
@else
@if (session('status'))
    <p class="portal-message" role="status">
        {{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="portal-message" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endif
