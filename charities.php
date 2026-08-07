<?php
/**
 * Charity directory listing page — unified PHP entry point
 * Replaces charities.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Charities — Ummah Directory';
$pageDescription = 'Support verified charities, zakat, sadaqah and emergency relief campaigns.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'charities.js';
[$lang, $dir] = getLangAndDir();

$mainContent = '
      <div class="page-head">
        <h1>Charities &amp; causes</h1>
        <p id="resultCount">Loading…</p>
      </div>

      <form class="filters" id="filtersForm">
        <div class="filter">
          <label for="f-q">Search</label>
          <input type="text" id="f-q" name="q" placeholder="Foundation, cause…">
        </div>
        <div class="filter">
          <label for="f-category">Category</label>
          <select id="f-category" name="category"><option value="">All categories</option></select>
        </div>
        <div class="filter">
          <button class="btn btn-primary btn-block" type="submit">Search</button>
          <a class="btn btn-ghost btn-block" href="charities" style="margin-top:.4rem;">Clear</a>
        </div>
      </form>

      <div class="listing-layout" id="charityResults">
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
      </div>
      <div id="pagination"></div>
';

require_once __DIR__ . '/templates/base.php';
