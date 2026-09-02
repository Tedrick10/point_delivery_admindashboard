(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function hideLoader() {
        var loading = document.getElementById('loading');
        var loader = document.getElementById('loader');
        if (!loading && !loader) {
            return;
        }

        var target = loading || loader;
        if (prefersReducedMotion) {
            target.style.display = 'none';
            if (loading && loader) loader.style.display = 'none';
            return;
        }
        target.style.transition = 'opacity 0.35s ease, visibility 0.35s ease';
        target.style.opacity = '0';
        target.style.visibility = 'hidden';
        window.setTimeout(function () {
            target.style.display = 'none';
            if (loading && loader) loader.style.display = 'none';
        }, 360);
    }

    function initLoader() {
        // Hide even if window "load" already fired (common with cached assets / soft reloads).
        if (document.readyState === 'complete') {
            hideLoader();
        } else {
            window.addEventListener('load', hideLoader);
        }
        // Absolute failsafe — never leave the splash covering the app.
        window.setTimeout(hideLoader, 1500);
    }

    function enableMotion() {
        if (prefersReducedMotion) {
            return;
        }
        document.body.classList.add('js-motion-ready');
    }

    function forceVisible(selector, className) {
        document.querySelectorAll(selector).forEach(function (el) {
            el.classList.add(className);
        });
    }

    function initDashboardAnimations() {
        if (prefersReducedMotion) {
            forceVisible('.pds-dashboard-panel.pds-panel-animate', 'pds-panel-visible');
            forceVisible('.pds-stats-grid > [class*="col-"]', 'pds-stat-visible');
            return;
        }

        enableMotion();

        var panels = document.querySelectorAll('.pds-dashboard-panel.pds-panel-animate');
        panels.forEach(function (panel, i) {
            panel.style.transitionDelay = (0.08 + i * 0.06) + 's';
            if ('IntersectionObserver' in window) {
                var panelObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('pds-panel-visible');
                            panelObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.02, rootMargin: '0px 0px 8% 0px' });
                panelObserver.observe(panel);
            } else {
                panel.classList.add('pds-panel-visible');
            }
        });

        document.querySelectorAll('.pds-stats-grid').forEach(function (grid) {
            var cells = grid.querySelectorAll(':scope > [class*="col-"]');
            cells.forEach(function (cell, i) {
                cell.classList.add('pds-stat-animate');
                cell.style.transitionDelay = Math.min(i * 0.04, 0.3) + 's';
            });

            if (!('IntersectionObserver' in window)) {
                forceVisible('.pds-stats-grid > [class*="col-"]', 'pds-stat-visible');
                return;
            }

            var statObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('pds-stat-visible');
                        statObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.02, rootMargin: '0px 0px 8% 0px' });

            cells.forEach(function (cell) {
                statObserver.observe(cell);
            });
        });

        // Failsafe: never leave panels/stats invisible if observer never fires.
        window.setTimeout(function () {
            forceVisible('.pds-dashboard-panel.pds-panel-animate', 'pds-panel-visible');
            forceVisible('.pds-stats-grid > [class*="col-"].pds-stat-animate', 'pds-stat-visible');
        }, 900);
    }

    function initCardReveal() {
        var selectors = [
            '.content-page .pds-page-card',
            '.content-page > .container-fluid > .card:not(.pds-form-card)',
            '.content-page .iq-card'
        ];

        var cards = [];
        selectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (card) {
                if (card.closest('.pds-stats-grid') || card.closest('.pds-dashboard-panel .card-body')) {
                    return;
                }
                if (cards.indexOf(card) === -1) {
                    cards.push(card);
                }
            });
        });

        if (prefersReducedMotion || !cards.length) {
            cards.forEach(function (card) {
                card.classList.add('pds-admin-visible');
            });
            return;
        }

        enableMotion();
        cards.forEach(function (card, i) {
            card.classList.add('pds-admin-reveal', 'pds-admin-visible');
            card.style.transitionDelay = Math.min(i * 0.04, 0.3) + 's';
        });
    }

    function initSidebar() {
        if (prefersReducedMotion) {
            return;
        }

        document.querySelectorAll('.iq-sidebar-menu .side-menu > li, .mm-sidebar-menu .side-menu > li').forEach(function (item, i) {
            item.style.animation = 'pdsAdminSlideIn 0.38s cubic-bezier(0.4, 0, 0.2, 1) ' + Math.min(i * 0.025, 0.35) + 's both';
        });
    }

    function initMobileSidebarDrawer() {
        document.addEventListener('click', function (e) {
            if (window.innerWidth > 1299) {
                return;
            }
            if (!document.body.classList.contains('sidebar-main')) {
                return;
            }
            if (e.target.closest('.mm-sidebar, .pds-sidebar, .wrapper-menu, .side-menu-bt-sidebar, .pds-sidebar-toggle')) {
                return;
            }
            document.body.classList.remove('sidebar-main');
            document.querySelectorAll('.wrapper-menu.open').forEach(function (el) {
                el.classList.remove('open');
            });
        });
    }

    function initStatCountUp() {
        if (prefersReducedMotion) {
            return;
        }

        document.querySelectorAll('.mm-cart-text h5.font-weight-700').forEach(function (el) {
            var raw = (el.textContent || '').trim();
            if (!/^\d+$/.test(raw)) {
                return;
            }

            var num = parseInt(raw, 10);
            var duration = 700;
            var startTime = null;

            function step(timestamp) {
                if (!startTime) {
                    startTime = timestamp;
                }
                var progress = Math.min((timestamp - startTime) / duration, 1);
                el.textContent = String(Math.floor(progress * num));
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = raw;
                }
            }

            el.textContent = '0';
            requestAnimationFrame(step);
        });
    }

    function initTableRows() {
        if (prefersReducedMotion) {
            return;
        }

        document.querySelectorAll('.dataTable tbody tr, table.dataTable tbody tr, .table tbody tr').forEach(function (row, i) {
            if (i > 20) {
                return;
            }
            row.style.animation = 'pdsAdminFadeUp 0.35s ease ' + Math.min(i * 0.03, 0.25) + 's both';
        });
    }

    function initLegacyPageEnhancements() {
        document.querySelectorAll('.content-page > .container-fluid').forEach(function (wrap) {
            if (!wrap.classList.contains('pds-page-wrap')) {
                wrap.classList.add('pds-page-wrap', 'pds-legacy-page', 'pds-motion-enter');
            }
        });

        document.querySelectorAll('.card-header .card-title').forEach(function (title) {
            title.classList.add('pds-page-title');
        });

        document.querySelectorAll('.card-header').forEach(function (header) {
            if (!header.classList.contains('pds-page-header')) {
                header.classList.add('pds-page-header');
            }
        });

        document.querySelectorAll('.dataTables_wrapper').forEach(function (wrapper) {
            var parent = wrapper.parentElement;
            if (parent && !parent.classList.contains('pds-table-shell')) {
                parent.classList.add('pds-table-shell');
            }
        });

        document.querySelectorAll('table.dataTable, table.pds-datatable').forEach(function (table) {
            if (!table.classList.contains('pds-datatable')) {
                table.classList.add('pds-datatable');
            }
        });
    }

    function initOrderDetailAnimations() {
        if (prefersReducedMotion) {
            document.querySelectorAll('.pds-order-detail .pds-order-animate').forEach(function (el) {
                el.style.opacity = '1';
                el.style.transform = 'none';
                el.classList.add('pds-order-visible');
            });
            return;
        }

        enableMotion();

        if (!('IntersectionObserver' in window)) {
            forceVisible('.pds-order-detail .pds-order-animate', 'pds-order-visible');
            return;
        }

        var blocks = document.querySelectorAll('.pds-order-detail .pds-order-block:not(.pds-order-animate)');
        blocks.forEach(function (block, i) {
            block.classList.add('pds-order-animate');
            block.style.animationDelay = (0.1 + i * 0.07) + 's';
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('pds-order-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.06 });

        document.querySelectorAll('.pds-order-detail .pds-order-main-card, .pds-order-detail .pds-order-user-card, .pds-order-detail .pds-order-history-card').forEach(function (card, i) {
            card.style.animation = 'pdsOrderReveal 0.55s cubic-bezier(0.22, 1, 0.36, 1) ' + (0.15 + i * 0.08) + 's both';
            card.classList.add('pds-order-visible');
            observer.observe(card);
        });

        window.setTimeout(function () {
            forceVisible('.pds-order-detail .pds-order-animate', 'pds-order-visible');
        }, 900);
    }

    function boot() {
        initLoader();
        initSidebar();
        initMobileSidebarDrawer();
        initDashboardAnimations();
        initCardReveal();
        initOrderDetailAnimations();
        initStatCountUp();
        initLegacyPageEnhancements();

        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('draw.dt', function () {
                initTableRows();
                initLegacyPageEnhancements();
            });
        }

        window.setTimeout(function () {
            initTableRows();
            initLegacyPageEnhancements();
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Back/forward cache restore — re-hide loader and force content visible.
    window.addEventListener('pageshow', function () {
        hideLoader();
        forceVisible('.pds-dashboard-panel.pds-panel-animate', 'pds-panel-visible');
        forceVisible('.pds-stats-grid > [class*="col-"].pds-stat-animate', 'pds-stat-visible');
        forceVisible('.pds-admin-reveal', 'pds-admin-visible');
        forceVisible('.pds-order-detail .pds-order-animate', 'pds-order-visible');
    });
})();
