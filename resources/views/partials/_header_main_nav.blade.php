@php
    $pdsUseHeaderNav = Auth::check() && (
        isDispatchHub(Auth::user())
        || ! in_array(Auth::user()->user_type, ['client', 'delivery_man'], true)
    );
    // Menu is built in sidebar; Blade include scope does not leak $MyNavBar, but
    // Lavary shares "MenuList" and we also share "MyNavBar" after filter.
    $pdsNavBar = $MyNavBar ?? ($MenuList ?? null);
    if (! $pdsNavBar && class_exists(\Lavary\Menu\Facade::class)) {
        try {
            $pdsNavBar = \Menu::get('MenuList');
        } catch (\Throwable $e) {
            $pdsNavBar = null;
        }
    }
    $pdsNavRoots = $pdsNavBar ? $pdsNavBar->roots() : collect();
@endphp

@if($pdsUseHeaderNav)
<div class="pds-main-nav" aria-label="Main">
    <button type="button" class="pds-main-nav__mobile-toggle" id="pdsMainNavToggle" aria-expanded="false" aria-controls="pdsMainNavList">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </button>

    <ul class="pds-main-nav__list" id="pdsMainNavList">
        @forelse($pdsNavRoots as $item)
            @php
                $hasChildren = $item->hasChildren();
                $titleHtml = $item->title;
                $url = $item->url();
                $isHash = is_string($url) && str_starts_with((string) $url, '#');
                $active = !empty($item->isActive) ? 'is-active' : '';
            @endphp
            <li class="pds-main-nav__item {{ $hasChildren ? 'has-dropdown' : '' }} {{ $active }}">
                @if($hasChildren)
                    <button type="button" class="pds-main-nav__link pds-main-nav__link--dropdown {{ $active }}" aria-expanded="false">
                        <span class="pds-main-nav__label">{!! $titleHtml !!}</span>
                        <i class="fas fa-chevron-down pds-main-nav__caret" aria-hidden="true"></i>
                    </button>
                    <div class="pds-main-nav__dropdown" role="menu">
                        <div class="pds-main-nav__dropdown-inner">
                            @foreach($item->children() as $child)
                                @if(!checkMenuRoleAndPermission($child))
                                    @continue
                                @endif
                                @php
                                    $childActive = !empty($child->isActive) ? 'is-active' : '';
                                @endphp
                                <a href="{{ $child->url() }}" class="pds-main-nav__sublink {{ $childActive }}" role="menuitem">
                                    {!! $child->title !!}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ $isHash ? 'javascript:void(0)' : $url }}" class="pds-main-nav__link {{ $active }}">
                        <span class="pds-main-nav__label">{!! $titleHtml !!}</span>
                    </a>
                @endif
            </li>
        @empty
        @endforelse
    </ul>
</div>

@once
<script>
(function () {
    function initPdsMainNav() {
        var nav = document.querySelector('.pds-main-nav');
        if (!nav || nav.dataset.pdsReady === '1') return;
        nav.dataset.pdsReady = '1';

        var toggle = document.getElementById('pdsMainNavToggle');
        var list = document.getElementById('pdsMainNavList');
        var portalHost = null;

        // Horizontal scroll: trackpad/mouse wheel + click-drag
        if (list) {
            list.addEventListener('wheel', function (e) {
                if (list.scrollWidth <= list.clientWidth + 1) return;
                var delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
                if (delta === 0) return;
                var atStart = list.scrollLeft <= 0 && delta < 0;
                var atEnd = list.scrollLeft + list.clientWidth >= list.scrollWidth - 1 && delta > 0;
                if (atStart || atEnd) return;
                list.scrollLeft += delta;
                e.preventDefault();
            }, { passive: false });

            var drag = { active: false, startX: 0, startScroll: 0, moved: false };
            list.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                if (e.target.closest('a, button, input, label')) return;
                drag.active = true;
                drag.moved = false;
                drag.startX = e.clientX;
                drag.startScroll = list.scrollLeft;
                list.setPointerCapture(e.pointerId);
            });
            list.addEventListener('pointermove', function (e) {
                if (!drag.active) return;
                var dx = e.clientX - drag.startX;
                if (Math.abs(dx) > 4) drag.moved = true;
                list.scrollLeft = drag.startScroll - dx;
            });
            list.addEventListener('pointerup', function () { drag.active = false; });
            list.addEventListener('pointercancel', function () { drag.active = false; });
            list.addEventListener('click', function (e) {
                if (drag.moved) {
                    e.preventDefault();
                    e.stopPropagation();
                    drag.moved = false;
                }
            }, true);
        }

        function ensurePortal() {
            if (portalHost && document.body.contains(portalHost)) return portalHost;
            portalHost = document.createElement('div');
            portalHost.id = 'pdsMainNavPortal';
            portalHost.className = 'pds-main-nav-portal';
            document.body.appendChild(portalHost);
            return portalHost;
        }

        function isMobileNav() {
            return window.matchMedia('(max-width: 1199.98px)').matches;
        }

        function restoreDropdown(item) {
            var dropdown = item._pdsDropdownEl || item.querySelector('.pds-main-nav__dropdown');
            if (!dropdown) return;
            item._pdsDropdownEl = dropdown;
            if (dropdown.parentElement !== item) {
                item.appendChild(dropdown);
            }
            dropdown.style.top = '';
            dropdown.style.left = '';
            dropdown.style.width = '';
        }

        function positionDropdown(item) {
            var dropdown = item._pdsDropdownEl || item.querySelector('.pds-main-nav__dropdown');
            var btn = item.querySelector('.pds-main-nav__link--dropdown');
            if (!dropdown || !btn) return;
            item._pdsDropdownEl = dropdown;

            if (isMobileNav()) {
                restoreDropdown(item);
                return;
            }

            var host = ensurePortal();
            if (dropdown.parentElement !== host) {
                host.appendChild(dropdown);
            }

            var rect = btn.getBoundingClientRect();
            var pad = 12;
            dropdown.classList.add('is-ported');
            // force layout for width
            dropdown.style.visibility = 'hidden';
            dropdown.style.opacity = '1';
            dropdown.style.pointerEvents = 'none';
            dropdown.style.display = 'block';
            var width = Math.max(dropdown.offsetWidth || 280, 280);
            var left = rect.left;
            if (left + width > window.innerWidth - pad) {
                left = Math.max(pad, window.innerWidth - width - pad);
            }
            if (left < pad) left = pad;
            dropdown.style.top = Math.round(rect.bottom + 8) + 'px';
            dropdown.style.left = Math.round(left) + 'px';
            dropdown.style.visibility = '';
            dropdown.style.opacity = '';
            dropdown.style.pointerEvents = '';
            dropdown.style.display = '';
        }

        function closeAllDropdowns() {
            nav.querySelectorAll('.pds-main-nav__item.is-open').forEach(function (el) {
                el.classList.remove('is-open');
                var btn = el.querySelector('.pds-main-nav__link--dropdown');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                restoreDropdown(el);
                var dd = el._pdsDropdownEl;
                if (dd) dd.classList.remove('is-open-ported');
            });
            if (portalHost) {
                portalHost.querySelectorAll('.pds-main-nav__dropdown').forEach(function (dd) {
                    dd.classList.remove('is-open-ported');
                });
            }
        }

        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var open = nav.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (!open) closeAllDropdowns();
            });
        }

        nav.querySelectorAll('.pds-main-nav__link--dropdown').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var item = btn.closest('.pds-main-nav__item');
                var willOpen = !item.classList.contains('is-open');
                closeAllDropdowns();
                if (willOpen) {
                    item.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                    positionDropdown(item);
                    var dd = item._pdsDropdownEl;
                    if (dd) dd.classList.add('is-open-ported');
                }
            });
        });

        window.addEventListener('resize', function () {
            var openItem = nav.querySelector('.pds-main-nav__item.is-open');
            if (openItem) positionDropdown(openItem);
        });

        window.addEventListener('scroll', function () {
            var openItem = nav.querySelector('.pds-main-nav__item.is-open');
            if (openItem) positionDropdown(openItem);
        }, true);

        document.addEventListener('click', function (e) {
            var inNav = nav.contains(e.target);
            var inPortal = e.target.closest && e.target.closest('#pdsMainNavPortal');
            if (!inNav && !inPortal) {
                nav.classList.remove('is-open');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                closeAllDropdowns();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                nav.classList.remove('is-open');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                closeAllDropdowns();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPdsMainNav);
    } else {
        initPdsMainNav();
    }
})();
</script>
@endonce
@endif
