<form class="documents-toolbar" method="GET">
    <div class="document-search">
        <label for="recordSearch">Search {{ strtolower($label) }}</label>
        <input type="search" id="recordSearch" name="search" value="{{ request('search') }}"
            placeholder="Search reference, type, details or status" maxlength="255">
    </div>
    <div class="document-page-size">
        <label for="pageSize">Show</label>
        <select id="pageSize" name="per_page">
            @foreach ([2, 5, 10, 25] as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
            @endforeach
        </select>
        <span>entries</span>
    </div>
    <button class="btn" type="submit">Search</button>
</form>
