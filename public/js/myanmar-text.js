/**
 * Admin Panel: Zawgyi / Unicode — detect + convert so typed & displayed Myanmar text looks correct.
 * Depends on rabbit.js (window.Rabbit with zg2uni).
 */
(function (window, document) {
  'use strict';

  var MM = /[\u1000-\u109F\uAA60-\uAA7F\uA9E0-\uA9FF]/;
  var ZG_MARK = /[\u1033\u1034\u105A\u1060-\u1097\u1099-\u109D]/;

  var unicodeRules = [
    /[ဃငဆဇဈဉညဋဌဍဎဏဒဓနဘရဝဟဠအ]်/,
    /ျ[က-အ]ါ/,
    /ျ[ါ-း]/,
    /\u103e/,
    /\u103f/,
    /\u100a\u103a/,
    /\u1014\u103a/,
    /\u1031\u1038/,
    /\u1031\u102c/,
    /\u103a\u1038/,
    /\u1035/,
    /[\u1050-\u1059]/,
  ];

  var zawgyiRules = [
    /\u102c\u1039/,
    /\u103a\u102c/,
    /^(\u103b|\u1031|[\u107e-\u1084])[\u1000-\u1021]/,
    /[\u1000-\u1021]\u1039[^\u1000-\u1021]/,
    /\u1025\u1039/,
    /\u1039\u1038/,
    /\u1036\u102f/,
    /\u1064/,
    /\u102c\u1031/,
    /\u1031\u1031/,
    /\u102f\u102d/,
    /\u1039$/,
  ];

  function hasMyanmar(text) {
    return typeof text === 'string' && MM.test(text);
  }

  function regexPrefersZawgyi(text) {
    var u = 0;
    var z = 0;
    unicodeRules.forEach(function (re) {
      if (re.test(text)) u += 1;
    });
    zawgyiRules.forEach(function (re) {
      if (re.test(text)) z += 1;
    });
    return z > u;
  }

  function looksLikeZawgyi(text) {
    if (!hasMyanmar(text)) return false;
    if (ZG_MARK.test(text)) return true;
    return regexPrefersZawgyi(text);
  }

  function toUnicode(text) {
    if (!hasMyanmar(text) || !looksLikeZawgyi(text)) return text;
    if (!window.Rabbit || typeof window.Rabbit.zg2uni !== 'function') return text;
    try {
      return window.Rabbit.zg2uni(text);
    } catch (e) {
      return text;
    }
  }

  function normalizeInput(el) {
    if (!el || el.dataset.pdsMmSkip === '1') return;
    var type = (el.type || '').toLowerCase();
    if (['password', 'email', 'tel', 'number', 'url', 'file', 'hidden', 'checkbox', 'radio', 'date', 'time', 'datetime-local'].indexOf(type) !== -1) {
      return;
    }
    if (el.value == null || el.value === '') return;
    var next = toUnicode(el.value);
    if (next !== el.value) {
      el.value = next;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function walkText(node) {
    if (!node) return;
    if (node.nodeType === 3) {
      var raw = node.nodeValue;
      if (!raw || !hasMyanmar(raw)) return;
      var converted = toUnicode(raw);
      if (converted !== raw) node.nodeValue = converted;
      return;
    }
    if (node.nodeType !== 1) return;
    var tag = (node.tagName || '').toLowerCase();
    if (tag === 'script' || tag === 'style' || tag === 'textarea' || tag === 'input' || tag === 'code' || tag === 'pre') {
      return;
    }
    if (node.isContentEditable) return;
    var child = node.firstChild;
    while (child) {
      var next = child.nextSibling;
      walkText(child);
      child = next;
    }
  }

  function bindInputs(root) {
    root = root || document;
    root.querySelectorAll('input[type="text"], input:not([type]), textarea').forEach(function (el) {
      if (el.dataset.pdsMmBound === '1') return;
      el.dataset.pdsMmBound = '1';
      el.addEventListener('blur', function () {
        normalizeInput(el);
      });
      el.addEventListener('paste', function () {
        setTimeout(function () {
          normalizeInput(el);
        }, 0);
      });
    });
  }

  function run() {
    bindInputs(document);
    walkText(document.body);
  }

  window.PdsMyanmarText = {
    toUnicode: toUnicode,
    looksLikeZawgyi: looksLikeZawgyi,
    normalizeInput: normalizeInput,
    refresh: run,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  // DataTables / AJAX redraws
  document.addEventListener('draw.dt', function () {
    setTimeout(run, 50);
  });
})(window, document);
