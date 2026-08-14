/* ==========================================================================
   HRRAMS front-end behaviour
   Theme switching · sidebar · toasts · multi-step wizard · uploads ·
   password meter · cascading selects · confirmation dialogs · lazy loading
   ========================================================================== */
(function () {
  'use strict';

  const $  = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.prototype.slice.call((ctx || document).querySelectorAll(sel));

  /* ------------------------------------------------------------ dark mode */
  const THEME_KEY = 'hrrams-theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    $$('#themeToggle i').forEach((icon) => {
      icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    });
  }

  function initTheme() {
    let stored = null;
    try { stored = localStorage.getItem(THEME_KEY); } catch (e) { /* private mode */ }
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(stored || (prefersDark ? 'dark' : 'light'));

    $$('#themeToggle').forEach((btn) => btn.addEventListener('click', () => {
      const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      try { localStorage.setItem(THEME_KEY, next); } catch (e) { /* ignore */ }
      document.dispatchEvent(new CustomEvent('hrrams:themechange', { detail: { theme: next } }));
    }));
  }

  /* -------------------------------------------------------------- sidebar */
  function initSidebar() {
    const sidebar  = $('#appSidebar');
    const backdrop = $('#sidebarBackdrop');
    if (!sidebar) return;

    const close = () => {
      sidebar.classList.remove('open');
      if (backdrop) backdrop.classList.remove('show');
    };

    $$('#sidebarToggle').forEach((btn) => btn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (backdrop) backdrop.classList.toggle('show');
    }));
    if (backdrop) backdrop.addEventListener('click', close);
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') close(); });
  }

  /* --------------------------------------------------------------- toasts */
  function initToasts() {
    if (!window.bootstrap) return;
    $$('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el, { delay: 6000 }).show());
  }

  /* --------------------------------------------------- confirm before act */
  function initConfirms() {
    $$('[data-confirm]').forEach((el) => {
      el.addEventListener('click', (ev) => {
        if (!window.confirm(el.getAttribute('data-confirm'))) {
          ev.preventDefault();
          ev.stopPropagation();
        }
      });
    });

    // Stop double submissions on every non-GET form.
    $$('form').forEach((form) => {
      form.addEventListener('submit', () => {
        const btn = form.querySelector('[type="submit"]');
        if (!btn || form.dataset.noLock === '1') return;
        window.setTimeout(() => {
          btn.disabled = true;
          btn.dataset.originalHtml = btn.innerHTML;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Working…';
        }, 0);
        // Re-enable if validation kept us on the page.
        window.setTimeout(() => {
          if (!btn.dataset.originalHtml) return;
          btn.disabled = false;
          btn.innerHTML = btn.dataset.originalHtml;
        }, 12000);
      });
    });
  }

  /* ----------------------------------------------------- password meter */
  function scorePassword(value) {
    let score = 0;
    if (value.length >= 10) score++;
    if (value.length >= 14) score++;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return Math.min(score, 5);
  }

  function initPasswordMeter() {
    $$('[data-pw-meter]').forEach((input) => {
      const meter = $('#' + input.getAttribute('data-pw-meter'));
      if (!meter) return;
      const bar   = meter.querySelector('span');
      const label = $('#' + input.getAttribute('data-pw-meter') + '-label');

      input.addEventListener('input', () => {
        const score = scorePassword(input.value);
        const pct   = (score / 5) * 100;
        const map   = [
          ['Very weak', '#dc3545'],
          ['Weak',      '#dc3545'],
          ['Fair',      '#ffc107'],
          ['Good',      '#20c997'],
          ['Strong',    '#198754'],
          ['Excellent', '#198754']
        ];
        bar.style.width = (input.value ? Math.max(pct, 8) : 0) + '%';
        bar.style.background = map[score][1];
        if (label) label.textContent = input.value ? map[score][0] : '';
      });
    });

    // Show / hide password
    $$('[data-toggle-password]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = $('#' + btn.getAttribute('data-toggle-password'));
        if (!target) return;
        const show = target.type === 'password';
        target.type = show ? 'text' : 'password';
        btn.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      });
    });
  }

  /* -------------------------------------------------- cascading province */
  function initCascadingDistricts() {
    $$('[data-districts-for]').forEach((select) => {
      const province = $('#' + select.getAttribute('data-districts-for'));
      if (!province) return;

      province.addEventListener('change', async () => {
        const id = province.value;
        select.innerHTML = '<option value="">Loading…</option>';
        select.disabled = true;
        if (!id) {
          select.innerHTML = '<option value="">Select a province first</option>';
          return;
        }
        try {
          const res = await fetch(select.dataset.endpoint.replace('__ID__', encodeURIComponent(id)), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          const rows = await res.json();
          select.innerHTML = '<option value="">Select district</option>';
          (Array.isArray(rows) ? rows : []).forEach((row) => {
            const opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row.name;
            select.appendChild(opt);
          });
        } catch (err) {
          select.innerHTML = '<option value="">Could not load districts</option>';
        } finally {
          select.disabled = false;
        }
      });
    });
  }

  /* ---------------------------------------------------- reporting wizard */
  function initWizard() {
    const wizard = $('[data-wizard]');
    if (!wizard) return;

    const panes = $$('.wizard-pane', wizard);
    const steps = $$('.wizard-steps li', wizard);
    let current = 0;

    function paint() {
      panes.forEach((pane, i) => pane.classList.toggle('active', i === current));
      steps.forEach((step, i) => {
        step.classList.toggle('current', i === current);
        step.classList.toggle('done', i < current);
      });
      const back = $('[data-wizard-back]', wizard);
      const next = $('[data-wizard-next]', wizard);
      const submit = $('[data-wizard-submit]', wizard);
      if (back)   back.classList.toggle('d-none', current === 0);
      if (next)   next.classList.toggle('d-none', current === panes.length - 1);
      if (submit) submit.classList.toggle('d-none', current !== panes.length - 1);
      wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /** HTML5 validation, one step at a time. */
    function stepValid() {
      const fields = $$('input, select, textarea', panes[current]);
      for (let i = 0; i < fields.length; i++) {
        if (!fields[i].checkValidity()) {
          fields[i].reportValidity();
          fields[i].classList.add('is-invalid');
          return false;
        }
        fields[i].classList.remove('is-invalid');
      }
      return true;
    }

    const nextBtn = $('[data-wizard-next]', wizard);
    const backBtn = $('[data-wizard-back]', wizard);
    if (nextBtn) nextBtn.addEventListener('click', () => {
      if (!stepValid()) return;
      current = Math.min(current + 1, panes.length - 1);
      paint();
      buildSummary();
    });
    if (backBtn) backBtn.addEventListener('click', () => {
      current = Math.max(current - 1, 0);
      paint();
    });

    // Anonymous toggle hides the identity block and drops its requirements.
    const anon = $('#is_anonymous', wizard);
    if (anon) {
      const block = $('#identityBlock', wizard);
      const sync = () => {
        if (!block) return;
        block.classList.toggle('d-none', anon.checked);
        $$('input, select, textarea', block).forEach((f) => {
          if (f.dataset.wasRequired === undefined) {
            f.dataset.wasRequired = f.required ? '1' : '0';
          }
          f.required = anon.checked ? false : f.dataset.wasRequired === '1';
          if (anon.checked) f.value = '';
        });
      };
      anon.addEventListener('change', sync);
      sync();
    }

    /** Read-back panel on the confirmation step. */
    function buildSummary() {
      const target = $('#wizardSummary', wizard);
      if (!target || current !== panes.length - 1) return;
      const rows = [];
      $$('[data-summary]', wizard).forEach((field) => {
        let value = field.value;
        if (field.tagName === 'SELECT' && field.selectedIndex >= 0) {
          value = field.options[field.selectedIndex].text;
        }
        if (field.type === 'checkbox') value = field.checked ? 'Yes' : 'No';
        if (!value || value === 'Select district') return;
        rows.push(
          '<div class="col-sm-6"><div class="text-muted fs-tiny">' +
          escapeHtml(field.getAttribute('data-summary')) +
          '</div><div class="fw-semibold small">' + escapeHtml(value) + '</div></div>'
        );
      });
      const files = $('#evidenceInput');
      if (files && files.files.length) {
        rows.push('<div class="col-sm-6"><div class="text-muted fs-tiny">Evidence files</div>' +
          '<div class="fw-semibold small">' + files.files.length + ' attached</div></div>');
      }
      target.innerHTML = rows.join('') ||
        '<p class="text-muted small mb-0">Nothing captured yet.</p>';
    }

    paint();
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value == null ? '' : value);
    return div.innerHTML;
  }

  /* --------------------------------------------------------- file uploads */
  function initDropzones() {
    $$('[data-dropzone]').forEach((zone) => {
      const input = $('#' + zone.getAttribute('data-dropzone'));
      const list  = zone.parentElement.querySelector('[data-filelist]');
      if (!input) return;

      const maxMb = parseInt(zone.dataset.maxMb || '20', 10);

      function render() {
        if (!list) return;
        list.innerHTML = '';
        Array.prototype.forEach.call(input.files, (file) => {
          const tooBig = file.size > maxMb * 1024 * 1024;
          const chip = document.createElement('span');
          chip.className = 'file-chip' + (tooBig ? ' border-danger text-danger' : '');
          chip.innerHTML = '<i class="bi bi-paperclip"></i>' + escapeHtml(file.name) +
            ' <span class="text-muted">' + (file.size / 1048576).toFixed(1) + ' MB</span>' +
            (tooBig ? ' <i class="bi bi-exclamation-triangle-fill"></i>' : '');
          list.appendChild(chip);
        });
        if (input.files.length) {
          const clear = document.createElement('button');
          clear.type = 'button';
          clear.className = 'btn btn-link btn-sm p-0 ms-2';
          clear.textContent = 'Clear';
          clear.addEventListener('click', () => { input.value = ''; render(); });
          list.appendChild(clear);
        }
      }

      zone.addEventListener('click', () => input.click());
      zone.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); input.click(); }
      });
      input.addEventListener('change', render);

      ['dragenter', 'dragover'].forEach((evt) => zone.addEventListener(evt, (ev) => {
        ev.preventDefault(); zone.classList.add('dragover');
      }));
      ['dragleave', 'drop'].forEach((evt) => zone.addEventListener(evt, (ev) => {
        ev.preventDefault(); zone.classList.remove('dragover');
      }));
      zone.addEventListener('drop', (ev) => {
        if (ev.dataTransfer && ev.dataTransfer.files.length) {
          input.files = ev.dataTransfer.files;
          render();
        }
      });
    });
  }

  /* ----------------------------------------------------- geolocation pick */
  function initGeolocation() {
    const btn = $('#useMyLocation');
    if (!btn || !navigator.geolocation) return;
    btn.addEventListener('click', () => {
      const lat = $('#latitude');
      const lng = $('#longitude');
      const out = $('#geoStatus');
      if (out) out.textContent = 'Locating…';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          if (lat) lat.value = pos.coords.latitude.toFixed(6);
          if (lng) lng.value = pos.coords.longitude.toFixed(6);
          if (out) out.textContent = 'Coordinates captured.';
        },
        () => { if (out) out.textContent = 'Location unavailable — enter it manually.'; },
        { enableHighAccuracy: true, timeout: 8000 }
      );
    });
  }

  /* ------------------------------------------------------- lazy loading */
  function initLazyImages() {
    $$('img[data-src]').forEach((img) => {
      if ('loading' in HTMLImageElement.prototype) {
        img.loading = 'lazy';
        img.src = img.dataset.src;
        return;
      }
      if (!('IntersectionObserver' in window)) { img.src = img.dataset.src; return; }
      const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.src = entry.target.dataset.src;
          io.unobserve(entry.target);
        });
      });
      io.observe(img);
    });
  }

  /* ------------------------------------------------- auto-submit filters */
  function initFilters() {
    $$('[data-autosubmit]').forEach((el) => {
      el.addEventListener('change', () => el.form && el.form.submit());
    });
  }

  /* -------------------------------------------------------- session ping */
  function initIdleWarning() {
    const timeoutSeconds = parseInt(document.body.dataset.sessionTimeout || '0', 10);
    if (!timeoutSeconds) return;
    const warnAt = (timeoutSeconds - 120) * 1000;
    if (warnAt <= 0) return;
    let timer = window.setTimeout(warn, warnAt);
    function warn() {
      const box = document.createElement('div');
      box.className = 'alert alert-warning position-fixed bottom-0 start-50 translate-middle-x m-3 shadow';
      box.style.zIndex = '1095';
      box.textContent = 'Your session will expire in about 2 minutes. Save your work.';
      document.body.appendChild(box);
      window.setTimeout(() => box.remove(), 20000);
    }
    ['click', 'keydown'].forEach((evt) => document.addEventListener(evt, () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(warn, warnAt);
    }, { passive: true }));
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initSidebar();
    initToasts();
    initConfirms();
    initPasswordMeter();
    initCascadingDistricts();
    initWizard();
    initDropzones();
    initGeolocation();
    initLazyImages();
    initFilters();
    initIdleWarning();
  });

  window.HRRAMS = { escapeHtml: escapeHtml };
})();
