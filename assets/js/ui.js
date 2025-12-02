(() => {
  const body = document.body;
  const root = document.documentElement;
  const overlay = document.querySelector('.sidebar-overlay') || (() => {
    const el = document.createElement('div');
    el.className = 'sidebar-overlay';
    document.body.appendChild(el);
    return el;
  })();

  let lastFocused = null;

  function getFocusable(container) {
    return container.querySelectorAll(
      'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
    );
  }

  function trapFocus(e, container) {
    if (e.key !== 'Tab') return;
    const items = getFocusable(container);
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  function onEsc(e) {
    if (e.key !== 'Escape') return;
    const sidebar = document.querySelector('.sidebar.is-open');
    const toggleBtn = document.querySelector('[data-sidebar-toggle][aria-expanded="true"]');
    if (sidebar) closeSidebar(sidebar, toggleBtn);
  }

  function openSidebar(sidebar, toggleBtn) {
    lastFocused = document.activeElement;
    sidebar.classList.add('is-open');
    sidebar.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-show');
    overlay.style.display = 'block';
    body.classList.add('no-scroll');
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');

    const focusables = getFocusable(sidebar);
    (focusables[0] || sidebar).focus();

    sidebar.addEventListener('keydown', (e) => trapFocus(e, sidebar));
    document.addEventListener('keydown', onEsc, { once: true });
  }

  function closeSidebar(sidebar, toggleBtn) {
    // If a focused element is inside the sidebar, blur it first to avoid aria-hidden focus conflict
    if (sidebar.contains(document.activeElement)) {
      try { document.activeElement.blur(); } catch (_) {}
    }
    sidebar.classList.remove('is-open');
    sidebar.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-show');
    overlay.style.display = 'none';
    body.classList.remove('no-scroll');
    if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    if (lastFocused) lastFocused.focus();
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-sidebar-toggle]');
    if (btn) {
      const target = btn.dataset.target || '#appSidebar';
      const sidebar = document.querySelector(target);
      if (!sidebar) return;
      const isOpen = sidebar.classList.contains('is-open') || sidebar.classList.contains('open');
      if (isOpen) {
        closeSidebar(sidebar, btn);
        sidebar.classList.remove('open');
      } else {
        openSidebar(sidebar, btn);
        sidebar.classList.add('is-open');
      }
    }
  });

  overlay.addEventListener('click', () => {
    const sidebar = document.querySelector('.sidebar.is-open');
    const toggleBtn = document.querySelector('[data-sidebar-toggle][aria-expanded="true"]');
    if (sidebar) closeSidebar(sidebar, toggleBtn);
    const alt = document.querySelector('.sidebar.open');
    if (alt) {
      alt.classList.remove('open');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded','false');
    }
  });

  // ==============================
  // Single Theme - No Toggle
  // ==============================
  // Always use light theme with unified color palette
  root.setAttribute('data-theme', 'light');
})();
