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
