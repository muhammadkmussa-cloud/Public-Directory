/**
 * Ummah Directory — Lightweight Zero-Dependency SVG Chart Renderer
 * Renders time-series analytics (Impressions, Clicks, CTR) as responsive SVG charts.
 */

'use strict';

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderAnalyticsChart(containerId, series) {
  const container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
  if (!container) return;

  if (!series || !series.length) {
    container.innerHTML = '<div class="muted center" style="padding:2rem;">No data available for this timeframe</div>';
    return;
  }

  const width = Math.max(300, container.clientWidth || 600);
  const height = 240;
  const padding = { top: 30, right: 40, bottom: 40, left: 50 };

  const graphWidth = width - padding.left - padding.right;
  const graphHeight = height - padding.top - padding.bottom;

  const maxImp = series.reduce((max, s) => {
    const val = Number(s.impressions);
    return Math.max(max, isNaN(val) ? 0 : val);
  }, 1);

  const maxClick = series.reduce((max, s) => {
    const val = Number(s.clicks);
    return Math.max(max, isNaN(val) ? 0 : val);
  }, 1);

  const count = series.length;
  const stepX = count > 1 ? graphWidth / (count - 1) : graphWidth;

  const impPoints = series.map((s, i) => {
    const rawVal = Number(s.impressions);
    const val = isNaN(rawVal) ? 0 : Math.max(0, rawVal);
    const x = padding.left + (count > 1 ? i * stepX : graphWidth / 2);
    const y = padding.top + graphHeight - (val / maxImp) * graphHeight;
    return { x, y, data: s };
  });

  const clickPoints = series.map((s, i) => {
    const rawVal = Number(s.clicks);
    const val = isNaN(rawVal) ? 0 : Math.max(0, rawVal);
    const x = padding.left + (count > 1 ? i * stepX : graphWidth / 2);
    const y = padding.top + graphHeight - (val / maxClick) * graphHeight;
    return { x, y, data: s };
  });

  const impPathStr = impPoints.map((p, i) => (i === 0 ? `M ${p.x} ${p.y}` : `L ${p.x} ${p.y}`)).join(' ');
  const clickPathStr = clickPoints.map((p, i) => (i === 0 ? `M ${p.x} ${p.y}` : `L ${p.x} ${p.y}`)).join(' ');

  // Grid lines
  let gridHtml = '';
  const gridSteps = 4;
  for (let i = 0; i <= gridSteps; i++) {
    const y = padding.top + (graphHeight / gridSteps) * i;
    const rawVal = maxImp - (maxImp / gridSteps) * i;
    const val = maxImp < 5 ? rawVal.toFixed(1) : Math.round(rawVal);
    gridHtml += `
      <line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="#e0e0e0" stroke-dasharray="3,3" />
      <text x="${padding.left - 8}" y="${y + 4}" text-anchor="end" font-size="10" fill="#888">${val}</text>
    `;
  }

  // X Axis labels
  let xLabelsHtml = '';
  const labelInterval = Math.ceil(count / 7);
  series.forEach((s, i) => {
    if (i % labelInterval === 0 || i === count - 1) {
      const x = padding.left + (count > 1 ? i * stepX : graphWidth / 2);
      const shortDate = escapeHtml(s.date ? String(s.date).slice(5) : '');
      xLabelsHtml += `<text x="${x}" y="${height - 10}" text-anchor="middle" font-size="10" fill="#888">${shortDate}</text>`;
    }
  });

  // Data dots
  let dotsHtml = '';
  impPoints.forEach(p => {
    const dateStr = escapeHtml(p.data.date);
    const impVal = Number(p.data.impressions) || 0;
    const clickVal = Number(p.data.clicks) || 0;
    const ctrVal = Number(p.data.ctr) || 0;
    dotsHtml += `<circle cx="${p.x}" cy="${p.y}" r="4" fill="#14F528" stroke="#0fba1e" stroke-width="1.5">
      <title>${dateStr}: ${impVal} impressions, ${clickVal} clicks (${ctrVal}% CTR)</title>
    </circle>`;
  });
  clickPoints.forEach(p => {
    const dateStr = escapeHtml(p.data.date);
    const clickVal = Number(p.data.clicks) || 0;
    dotsHtml += `<circle cx="${p.x}" cy="${p.y}" r="3.5" fill="#2196F3" stroke="#1976D2" stroke-width="1.5">
      <title>${dateStr}: ${clickVal} clicks</title>
    </circle>`;
  });

  const svgHtml = `
    <div class="chart-wrapper" style="width:100%;overflow-x:auto;">
      <div style="display:flex;gap:1rem;font-size:0.85rem;margin-bottom:0.5rem;align-items:center;">
        <span style="display:inline-flex;align-items:center;gap:0.3rem;"><span style="width:12px;height:12px;background:#14F528;border-radius:2px;display:inline-block;"></span> Impressions (${maxImp} max)</span>
        <span style="display:inline-flex;align-items:center;gap:0.3rem;"><span style="width:12px;height:12px;background:#2196F3;border-radius:2px;display:inline-block;"></span> Clicks (${maxClick} max)</span>
      </div>
      <svg viewBox="0 0 ${width} ${height}" style="width:100%;height:auto;background:#fff;border:1px solid #e6e6e6;border-radius:8px;">
        ${gridHtml}
        ${xLabelsHtml}
        <path d="${impPathStr}" fill="none" stroke="#14F528" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        <path d="${clickPathStr}" fill="none" stroke="#2196F3" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
        ${dotsHtml}
      </svg>
    </div>
  `;

  container.innerHTML = svgHtml;
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { renderAnalyticsChart };
}
