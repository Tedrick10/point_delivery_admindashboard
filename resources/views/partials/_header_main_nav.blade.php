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
            @if($item->data('is_section'))
                @continue
            @endif
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
        <li class="pds-main-nav__item has-dropdown pds-main-nav__item--more is-empty" id="pdsMainNavMore" hidden>
            <button type="button" class="pds-main-nav__link pds-main-nav__link--dropdown" aria-expanded="false">
                <span class="pds-main-nav__label">More</span>
                <i class="fas fa-chevron-down pds-main-nav__caret" aria-hidden="true"></i>
            </button>
            <div class="pds-main-nav__dropdown" role="menu">
                <div class="pds-main-nav__dropdown-inner" id="pdsMainNavMoreInner"></div>
            </div>
        </li>
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
        var moreItem = document.getElementById('pdsMainNavMore');
        var moreInner = document.getElementById('pdsMainNavMoreInner');
        var portalHost = null;
        var topbar = document.querySelector('.mm-top-navbar.pds-topbar');
        var overflowTimer = null;

        function syncTopbarHeight() {
            if (!topbar || !document.body.classList.contains('pds-header-nav')) return;
            var h = Math.ceil(topbar.getBoundingClientRect().height);
            if (h > 0) {
                document.body.style.setProperty('--pds-topbar-h', h + 'px');
            }
        }

        function primaryItems() {
            if (!list) return [];
            return Array.prototype.slice.call(list.querySelectorAll(':scope > .pds-main-nav__item:not(.pds-main-nav__item--more)'));
        }

        function resetOverflow() {
            if (!moreInner || !moreItem) return;
            moreInner.innerHTML = '';
            primaryItems().forEach(function (item) {
                item.classList.remove('is-overflow-hidden');
            });
            moreItem.classList.add('is-empty');
            moreItem.hidden = true;
        }

        function moveItemToMore(item) {
            if (!item || !moreInner) return;
            item.classList.add('is-overflow-hidden');

            var labelEl = item.querySelector('.pds-main-nav__label');
            var labelText = 'Item';
            if (labelEl) {
                var labelClone = labelEl.cloneNode(true);
                labelClone.querySelectorAll('.badge').forEach(function (b) { b.remove(); });
                labelText = (labelClone.textContent || '').replace(/\s+/g, ' ').trim() || 'Item';
            }
            var badge = item.querySelector('.pds-main-nav__label .badge, .pds-main-nav__link > .badge');
            var badgeHtml = badge ? badge.outerHTML : '';

            if (item.classList.contains('has-dropdown')) {
                var children = item.querySelectorAll('.pds-main-nav__sublink');
                if (children.length) {
                    var group = document.createElement('div');
                    group.className = 'pds-main-nav__more-group';
                    var title = document.createElement('div');
                    title.className = 'pds-main-nav__more-title';
                    title.textContent = labelText;
                    group.appendChild(title);
                    children.forEach(function (child) {
                        group.appendChild(child.cloneNode(true));
                    });
                    // Keep original menu order: overflow from the end, insert at top.
                    moreInner.insertBefore(group, moreInner.firstChild);
                } else {
                    var stub = document.createElement('span');
                    stub.className = 'pds-main-nav__sublink';
                    stub.textContent = labelText;
                    moreInner.insertBefore(stub, moreInner.firstChild);
                }
                return;
            }

            var link = item.querySelector('a.pds-main-nav__link');
            var a = document.createElement('a');
            a.className = 'pds-main-nav__sublink' + (item.classList.contains('is-active') || (link && link.classList.contains('is-active')) ? ' is-active' : '');
            a.href = link ? link.getAttribute('href') : 'javascript:void(0)';
            var name = document.createElement('span');
            name.textContent = labelText;
            a.appendChild(name);
            if (badgeHtml) {
                a.insertAdjacentHTML('beforeend', badgeHtml);
            }
            moreInner.insertBefore(a, moreInner.firstChild);
        }

        function fitNavOverflow() {
            if (!list || !moreItem || !moreInner || isMobileNav()) {
                resetOverflow();
                syncTopbarHeight();
                return;
            }

            // 1) Show every primary item first (More hidden) so we measure true available width.
            resetOverflow();

            if (list.scrollWidth <= list.clientWidth + 1) {
                syncTopbarHeight();
                return;
            }

            // 2) Only then enable More and spill from the end.
            moreItem.hidden = false;
            moreItem.classList.remove('is-empty');

            var items = primaryItems();
            var safety = 0;
            while (list.scrollWidth > list.clientWidth + 1 && items.length > 1 && safety < 40) {
                safety += 1;
                moveItemToMore(items.pop());
            }

            if (!moreInner.childElementCount) {
                moreItem.classList.add('is-empty');
                moreItem.hidden = true;
            }

            syncTopbarHeight();
        }

        function scheduleOverflowFit() {
            if (overflowTimer) window.clearTimeout(overflowTimer);
            overflowTimer = window.setTimeout(fitNavOverflow, 40);
        }

        syncTopbarHeight();
        scheduleOverflowFit();
        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () {
                scheduleOverflowFit();
            });
            if (topbar) ro.observe(topbar);
            if (list) ro.observe(list);
        }
        window.addEventListener('resize', scheduleOverflowFit);

        function ensurePortal() {
            if (portalHost && document.body.contains(portalHost)) return portalHost;
            portalHost = document.createElement('div');
            portalHost.id = 'pdsMainNavPortal';
            portalHost.className = 'pds-main-nav-portal';
            document.body.appendChild(portalHost);
            return portalHost;
        }

        function isMobileNav() {
            return window.matchMedia('(max-width: 767.98px)').matches;
        }

        // Only one header dropdown open at a time (notify / language / profile / menu).
        document.addEventListener('show.bs.dropdown', function (e) {
            var open = document.querySelectorAll('.pds-topbar .dropdown-menu.show, .pds-topbar .dropdown.show > .dropdown-menu');
            open.forEach(function (menu) {
                if (e.target && menu.contains(e.target)) return;
                var toggle = menu.parentElement && menu.parentElement.querySelector('[data-toggle="dropdown"], [data-bs-toggle="dropdown"]');
                if (toggle && window.jQuery) {
                    try { window.jQuery(toggle).dropdown('hide'); } catch (err) {}
                } else {
                    menu.classList.remove('show');
                    if (menu.parentElement) menu.parentElement.classList.remove('show');
                }
            });
            if (nav.classList.contains('is-open')) {
                nav.classList.remove('is-open');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            }
        });

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
