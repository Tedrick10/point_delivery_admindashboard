@php
    $url = trim((string) ($url ?? ''));
    $title = (string) ($title ?? '');
    $large = !empty($large);
@endphp
@if($url !== '')
    <a href="{{ $url }}"
       target="_blank"
       rel="noopener"
       class="pds-mt-photo {{ $large ? 'pds-mt-photo--large' : '' }} js-mt-photo"
       title="{{ $title }}">
        <img src="{{ $url }}"
             alt=""
             loading="lazy"
             onerror="var a=this.closest('.js-mt-photo'); if(!a) return; var s=document.createElement('span'); s.className='pds-mt-photo-empty'; s.setAttribute('aria-hidden','true'); s.innerHTML='<i class=&quot;far fa-image&quot;></i>'; a.replaceWith(s);">
    </a>
@else
    <span class="pds-mt-photo-empty" aria-hidden="true"><i class="far fa-image"></i></span>
@endif
