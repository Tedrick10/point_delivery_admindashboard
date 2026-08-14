(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function hideLoader() {
        var loader = document.getElementById('loader');
        if (!loader) {
            return;
        }
        loader.classList.add('pds-loader--hide');
        window.setTimeout(function () {
            loader.style.display = 'none';
        }, 500);
    }

    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
    }

    var navbar = document.querySelector('.pds-navbar');
    function updateNavbar() {
        if (!navbar) {
            return;
        }
        navbar.classList.toggle('pds-navbar--scrolled', window.scrollY > 20);
    }

    window.addEventListener('scroll', updateNavbar, { passive: true });
    updateNavbar();

    var scrollBtn = document.getElementById('myBtn');
    function updateScrollBtn() {
        if (!scrollBtn) {
            return;
        }
        if (window.scrollY > 280) {
            scrollBtn.style.display = 'block';
            scrollBtn.classList.add('pds-scroll-visible');
        } else {
            scrollBtn.classList.remove('pds-scroll-visible');
            window.setTimeout(function () {
                if (!scrollBtn.classList.contains('pds-scroll-visible')) {
                    scrollBtn.style.display = 'none';
                }
            }, 300);
        }
    }

    window.addEventListener('scroll', updateScrollBtn, { passive: true });
    updateScrollBtn();

    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var targetId = this.getAttribute('href');
            if (!targetId || targetId === '#') {
                return;
            }
            var target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: prefersReducedMotion ? 'auto' : 'smooth',
                    block: 'start'
                });
            }
        });
    });

    function initHeroAnimations() {
        var hero = document.querySelector('.main-page > section:first-child');
        if (!hero) {
            return;
        }

        var title = hero.querySelector('h1');
        var subtitle = hero.querySelector('h2');
        var card = hero.querySelector('.card');
        var image = hero.querySelector('.col-lg-6:last-child img');

        [title, subtitle, card].forEach(function (el, index) {
            if (!el) {
                return;
            }
            el.classList.add('hero-animate-in', 'hero-animate-in-delay-' + (index + 1));
        });

        if (image && !prefersReducedMotion) {
            image.classList.add('hero-float-img', 'hero-animate-in', 'hero-animate-in-delay-3');
        }
    }

    function initScrollReveal() {
        var revealSelectors = [
            '.main-page section:not(:first-child)',
            '.main-page .card',
            '.step-box',
            '.timeline-item',
            '.contentcard',
            '.courier-section .col-lg-6',
            '.ordertracking-ul li',
            '.padding-top-80 section',
            '.contactus-section',
            '.delivery-partner-p',
            '.section-img',
            '.deliver-img',
            '.fixed-card'
        ];

        var elements = [];
        revealSelectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (el) {
                if (elements.indexOf(el) === -1) {
                    elements.push(el);
                }
            });
        });

        if (prefersReducedMotion) {
            elements.forEach(function (el) {
                el.classList.add('is-visible');
            });
            return;
        }

        elements.forEach(function (el, index) {
            el.classList.add('reveal-on-scroll');

            if (el.classList.contains('section-img') || el.classList.contains('courier-image')) {
                el.classList.add('reveal-right');
            }

            var parentRow = el.closest('.row.g-4, .row.g-3, .ordertracking-ul');
            if (parentRow) {
                var col = el.closest('[class*="col-"]') || el;
                var cols = parentRow.querySelectorAll(':scope > [class*="col-"], :scope > li');
                var colIndex = Array.prototype.indexOf.call(cols, col);
                if (colIndex >= 0) {
                    el.style.setProperty('--reveal-delay', (colIndex * 0.08) + 's');
                }
            } else {
                el.style.setProperty('--reveal-delay', ((index % 6) * 0.06) + 's');
            }
        });

        if (!('IntersectionObserver' in window)) {
            elements.forEach(function (el) {
                el.classList.add('is-visible');
            });
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
        );

        elements.forEach(function (el) {
            observer.observe(el);
        });
    }

    function initPageHeroStrips() {
        document.querySelectorAll('.padding-top-80 > section:first-child').forEach(function (section) {
            section.classList.add('page-hero-strip');
        });
    }

    function init() {
        initHeroAnimations();
        initPageHeroStrips();
        initScrollReveal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
