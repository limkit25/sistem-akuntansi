@props(['status'])

@if ($status)
    <div {{ $attributes }}>
        <div class="alert alert-success p-2" role="alert">
            <small>{{ $status }}</small>
        </div>
    </div>
@endif