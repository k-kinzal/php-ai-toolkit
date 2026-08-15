/* DocGen client script: search, theme toggle, copy buttons, mobile nav. */
(function () {
  'use strict';

  var root = document.body.getAttribute('data-root') || '';
  var input = document.getElementById('search');
  var results = document.getElementById('search-results');
  var selected = -1;

  function items() {
    return window.__DOCGEN_INDEX__ || [];
  }

  function score(item, query) {
    var name = item.n.toLowerCase();
    var full = item.f.toLowerCase();
    if (name === query) { return 0; }
    if (name.indexOf(query) === 0) { return 1; }
    var member = name.indexOf('::' + query);
    if (member !== -1) { return 2; }
    if (name.indexOf(query) !== -1) { return 3; }
    if (full.indexOf(query) !== -1) { return 4; }
    return -1;
  }

  function search(query) {
    query = query.trim().toLowerCase().replace(/^\\+/, '').replace(/\\+/g, '\\');
    if (query === '') { return []; }
    var hits = [];
    var list = items();
    for (var i = 0; i < list.length; i++) {
      var s = score(list[i], query);
      if (s !== -1) { hits.push({ s: s, len: list[i].n.length, item: list[i] }); }
    }
    hits.sort(function (a, b) { return a.s - b.s || a.len - b.len || (a.item.n < b.item.n ? -1 : 1); });
    return hits.slice(0, 30).map(function (h) { return h.item; });
  }

  function esc(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function renderResults(list) {
    if (!results) { return; }
    if (list.length === 0) {
      results.hidden = true;
      results.innerHTML = '';
      selected = -1;
      return;
    }
    var html = '';
    for (var i = 0; i < list.length; i++) {
      var item = list[i];
      html += '<a href="' + root + item.u + '">'
        + '<span class="chip chip-sm chip-kind k-' + item.k + '">' + item.k + '</span> '
        + '<span class="search-hit-name">' + esc(item.n) + '</span>'
        + '<span class="search-hit-full">' + esc(item.f) + '</span>'
        + (item.s ? '<span class="search-hit-summary">' + esc(item.s) + '</span>' : '')
        + '</a>';
    }
    results.innerHTML = html;
    results.hidden = false;
    selected = -1;
  }

  function moveSelection(delta) {
    if (!results || results.hidden) { return; }
    var links = results.querySelectorAll('a');
    if (links.length === 0) { return; }
    if (selected >= 0) { links[selected].classList.remove('selected'); }
    selected = (selected + delta + links.length) % links.length;
    links[selected].classList.add('selected');
    links[selected].scrollIntoView({ block: 'nearest' });
  }

  if (input) {
    input.addEventListener('input', function () { renderResults(search(input.value)); });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowDown') { event.preventDefault(); moveSelection(1); }
      if (event.key === 'ArrowUp') { event.preventDefault(); moveSelection(-1); }
      if (event.key === 'Enter' && results && !results.hidden) {
        var links = results.querySelectorAll('a');
        var target = selected >= 0 ? links[selected] : links[0];
        if (target) { window.location.href = target.getAttribute('href'); }
      }
      if (event.key === 'Escape') { renderResults([]); input.blur(); }
    });
  }

  document.addEventListener('keydown', function (event) {
    if ((event.key === '/' || event.key === 's') && input
      && document.activeElement !== input
      && !/^(input|textarea|select)$/i.test(document.activeElement.tagName)) {
      event.preventDefault();
      input.focus();
      input.select();
    }
  });

  document.addEventListener('click', function (event) {
    if (results && !results.hidden && !results.contains(event.target) && event.target !== input) {
      renderResults([]);
    }
    var copy = event.target.closest ? event.target.closest('.copy-btn') : null;
    if (copy) {
      var figure = copy.closest('figure');
      var pre = figure ? figure.querySelector('pre') : null;
      if (pre && navigator.clipboard) {
        navigator.clipboard.writeText(pre.textContent).then(function () {
          copy.textContent = 'copied';
          setTimeout(function () { copy.textContent = 'copy'; }, 1200);
        });
      }
    }
  });

  var themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var current = document.documentElement.dataset.theme
        || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      var next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      try { localStorage.setItem('docgen-theme', next); } catch (error) { /* private mode */ }
    });
  }

  var navToggle = document.getElementById('nav-toggle');
  if (navToggle) {
    navToggle.addEventListener('click', function () {
      document.body.classList.toggle('nav-open');
    });
  }
})();
