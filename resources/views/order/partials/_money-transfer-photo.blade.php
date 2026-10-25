@php
    $urls = collect($urls ?? [])
        ->filter(static fn ($u) => is_string($u) && trim($u) !== '')
        ->map(static fn ($u) => trim($u))
        ->unique()
        ->values()
        ->all();
    if ($urls === []) {
        $single = trim((string) ($url ?? ''));
        if ($single !== '') {
            $urls = [$single];
        }
    }
    $title = (string) ($title ?? '');
    $large = ! empty($large);
    $visibleLimit = 2;
    $total = count($urls);
    $extra = max(0, $total - $visibleLimit);
@endphp
@if($urls !== [])
    <div class="pds-mt-photo-gallery {{ $large ? 'pds-mt-photo-gallery--large' : '' }}"
         data-count="{{ $total }}"
         data-title="{{ $title }}">
        @foreach($urls as $photoUrl)
            @if($loop->iteration > $visibleLimit)
                @break
            @endif
            <a href="{{ $photoUrl }}"
               class="pds-mt-photo {{ $large ? 'pds-mt-photo--large' : '' }} js-mt-photo"
               data-index="{{ $loop->index }}"
               title="{{ $title }}{{ $total > 1 ? ' ('.($loop->iteration).'/'.$total.')' : '' }}">
                <img src="{{ $photoUrl }}"
                     alt=""
                     loading="lazy"
                     onerror="var a=this.closest('.js-mt-photo'); if(!a) return; a.remove();">
            </a>
        @endforeach
        @if($extra > 0)
            <button type="button"
                    class="pds-mt-photo-more js-mt-photo-more"
                    data-index="{{ $visibleLimit }}"
                    title="{{ $title }} (+{{ $extra }})">
                +{{ $extra }}
            </button>
        @endif
        {{-- Keep full URL list for lightbox (hidden). --}}
        <span class="pds-mt-photo-urls" hidden>
            @foreach($urls as $photoUrl)
                <span data-url="{{ $photoUrl }}"></span>
            @endforeach
        </span>
    </div>
@else
    <span class="pds-mt-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
@endif
