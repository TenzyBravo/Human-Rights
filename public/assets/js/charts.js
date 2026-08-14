/* ==========================================================================
   HRRAMS charts — Chart.js wrappers that respect the active theme.
   Any element with data-chart="<type>" and data-chart-src (JSON endpoint or
   inline JSON in data-chart-data) is rendered automatically.
   ========================================================================== */
(function () {
  'use strict';

  if (typeof Chart === 'undefined') return;

  const PALETTE = ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1',
                   '#fd7e14', '#0dcaf0', '#198754', '#d63384', '#6c757d',
                   '#495057', '#0a58ca'];

  const registry = [];

  function themeColors() {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    return {
      text: dark ? '#98a2b3' : '#6c757d',
      grid: dark ? 'rgba(255,255,255,.07)' : 'rgba(16,24,40,.07)',
      surface: dark ? '#161b22' : '#ffffff'
    };
  }

  function baseOptions(type) {
    const c = themeColors();
    const legendBottom = type === 'doughnut' || type === 'pie';
    return {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 500 },
      plugins: {
        legend: {
          display: legendBottom,
          position: 'bottom',
          labels: { color: c.text, boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 14 }
        },
        tooltip: {
          backgroundColor: c.surface,
          titleColor: c.text,
          bodyColor: c.text,
          borderColor: c.grid,
          borderWidth: 1,
          padding: 10,
          displayColors: true
        }
      },
      scales: legendBottom ? {} : {
        x: { ticks: { color: c.text, font: { size: 11 } }, grid: { display: false }, border: { display: false } },
        y: {
          beginAtZero: true,
          ticks: { color: c.text, precision: 0, font: { size: 11 } },
          grid: { color: c.grid },
          border: { display: false }
        }
      }
    };
  }

  function labelFor(raw) {
    if (raw == null || raw === '') return 'Unspecified';
    return String(raw).replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase());
  }

  function buildDataset(type, rows) {
    const labels = rows.map((r) => labelFor(r.label));
    const values = rows.map((r) => Number(r.value) || 0);

    if (type === 'line') {
      return {
        labels: labels,
        datasets: [{
          label: 'Reports',
          data: values,
          borderColor: PALETTE[0],
          backgroundColor: 'rgba(13,110,253,.12)',
          fill: true,
          tension: .35,
          pointRadius: 3,
          pointBackgroundColor: PALETTE[0],
          borderWidth: 2
        }]
      };
    }

    if (type === 'doughnut' || type === 'pie') {
      return {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
          borderWidth: 2,
          borderColor: themeColors().surface,
          hoverOffset: 6
        }]
      };
    }

    // bar / horizontalBar
    return {
      labels: labels,
      datasets: [{
        label: 'Reports',
        data: values,
        backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
        borderRadius: 6,
        maxBarThickness: 34
      }]
    };
  }

  function render(canvas, rows) {
    const declared = canvas.getAttribute('data-chart') || 'bar';
    const horizontal = declared === 'hbar';
    const type = horizontal ? 'bar' : declared;

    if (!rows || rows.length === 0) {
      const holder = canvas.parentElement;
      holder.innerHTML = '<div class="empty-state"><i class="bi bi-bar-chart"></i>' +
        'No data available for this period.</div>';
      return;
    }

    const options = baseOptions(type);
    if (horizontal) options.indexAxis = 'y';
    if (type === 'doughnut') options.cutout = '62%';

    const chart = new Chart(canvas.getContext('2d'), {
      type: type,
      data: buildDataset(declared, rows),
      options: options
    });
    registry.push({ chart: chart, declared: declared, rows: rows });
  }

  function pickSeries(payload, key) {
    if (Array.isArray(payload)) return payload;
    return payload && payload[key] ? payload[key] : [];
  }

  async function boot() {
    const canvases = Array.prototype.slice.call(document.querySelectorAll('[data-chart]'));
    if (canvases.length === 0) return;

    // Group canvases by endpoint so one fetch feeds several charts.
    const byEndpoint = new Map();
    canvases.forEach((canvas) => {
      const inline = canvas.getAttribute('data-chart-data');
      if (inline) {
        try { render(canvas, JSON.parse(inline)); } catch (e) { render(canvas, []); }
        return;
      }
      const src = canvas.getAttribute('data-chart-src');
      if (!src) { render(canvas, []); return; }
      if (!byEndpoint.has(src)) byEndpoint.set(src, []);
      byEndpoint.get(src).push(canvas);
    });

    for (const [src, group] of byEndpoint) {
      group.forEach((c) => {
        c.parentElement.insertAdjacentHTML('afterbegin',
          '<div class="skeleton position-absolute top-0 start-0 w-100 h-100" data-chart-skeleton></div>');
      });
      try {
        const res = await fetch(src, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await res.json();
        group.forEach((canvas) => {
          const skeleton = canvas.parentElement.querySelector('[data-chart-skeleton]');
          if (skeleton) skeleton.remove();
          render(canvas, pickSeries(payload, canvas.getAttribute('data-chart-key') || ''));
        });
      } catch (err) {
        group.forEach((canvas) => {
          const skeleton = canvas.parentElement.querySelector('[data-chart-skeleton]');
          if (skeleton) skeleton.remove();
          canvas.parentElement.innerHTML =
            '<div class="empty-state"><i class="bi bi-wifi-off"></i>Could not load chart data.</div>';
        });
      }
    }
  }

  /* Repaint axis/legend colours when the user flips the theme. */
  document.addEventListener('hrrams:themechange', function () {
    registry.forEach((entry) => {
      const options = baseOptions(entry.declared === 'hbar' ? 'bar' : entry.declared);
      if (entry.declared === 'hbar') options.indexAxis = 'y';
      if (entry.declared === 'doughnut') {
        options.cutout = '62%';
        entry.chart.data.datasets[0].borderColor = themeColors().surface;
      }
      entry.chart.options = options;
      entry.chart.update('none');
    });
  });

  /* Province x category heat map, rendered as an accessible table. */
  function initHeatmap() {
    const holder = document.querySelector('[data-heatmap]');
    if (!holder) return;
    let rows;
    try { rows = JSON.parse(holder.getAttribute('data-heatmap')); } catch (e) { return; }
    if (!rows || !rows.length) {
      holder.innerHTML = '<div class="empty-state"><i class="bi bi-grid-3x3"></i>No geographic data yet.</div>';
      return;
    }

    const provinces = Array.from(new Set(rows.map((r) => r.province)));
    const categories = Array.from(new Set(rows.map((r) => r.category)));
    const lookup = {};
    let max = 0;
    rows.forEach((r) => {
      lookup[r.province + '|' + r.category] = Number(r.value) || 0;
      max = Math.max(max, Number(r.value) || 0);
    });

    let html = '<table class="heatmap"><caption class="visually-hidden">' +
      'Reports by province and category</caption><thead><tr><th scope="col"></th>';
    provinces.forEach((p) => { html += '<th scope="col">' + esc(p.slice(0, 4)) + '</th>'; });
    html += '</tr></thead><tbody>';
    categories.forEach((cat) => {
      html += '<tr><th scope="row" class="text-start">' + esc(cat.length > 22 ? cat.slice(0, 21) + '…' : cat) + '</th>';
      provinces.forEach((prov) => {
        const value = lookup[prov + '|' + cat] || 0;
        const alpha = max ? (0.08 + (value / max) * 0.85) : 0.08;
        html += '<td title="' + esc(prov + ' · ' + cat + ': ' + value) + '" ' +
          'style="background:rgba(13,110,253,' + alpha.toFixed(2) + ')">' +
          (value || '') + '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table>';
    holder.innerHTML = html;
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = String(value == null ? '' : value);
    return div.innerHTML;
  }

  document.addEventListener('DOMContentLoaded', function () {
    boot();
    initHeatmap();
  });
})();
