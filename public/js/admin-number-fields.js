(function () {
    'use strict';

    function isNumberInput(el) {
        return el && el.tagName === 'INPUT' && (el.type === 'number' || el.getAttribute('data-pds-number') === '1');
    }

    function neutralize(el) {
        if (!el || el.tagName !== 'INPUT' || el.type !== 'number') {
            return;
        }

        var step = el.getAttribute('step');
        el.setAttribute('data-pds-number', '1');
        el.type = 'text';
        if (!el.getAttribute('inputmode')) {
            el.setAttribute('inputmode', step && step !== '1' ? 'decimal' : 'numeric');
        }
        if (!el.getAttribute('autocomplete')) {
            el.setAttribute('autocomplete', 'off');
        }
    }

    function neutralizeAll(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }
        root.querySelectorAll('input[type="number"]').forEach(neutralize);
    }

    function boot() {
        neutralizeAll(document);

        document.addEventListener('wheel', function (event) {
            if (!isNumberInput(event.target) || event.target.type !== 'number') {
                return;
            }
            event.preventDefault();
        }, { passive: false });

        document.addEventListener('keydown', function (event) {
            if (!isNumberInput(event.target) || event.target.type !== 'number') {
                return;
            }
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
            }
        });

        if (!window.MutationObserver || !document.body) {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }
                    if (node.matches && node.matches('input[type="number"]')) {
                        neutralize(node);
                    }
                    neutralizeAll(node);
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
