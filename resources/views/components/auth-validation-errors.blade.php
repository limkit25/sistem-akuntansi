@props(['errors'])

@if ($errors->any())
    <div {{ $attributes }}>
        <div class="alert alert-danger p-2" role="alert">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li><small>{{ $error }}</small></li>
                @endforeach
            </ul>
        </div>
    </div>
@endif