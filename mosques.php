<?php
/**
 * Mosque directory listing page — unified PHP entry point
 * Replaces mosques.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Mosques — Ummah Directory';
$pageDescription = 'Find mosques with prayer times, facilities and community reviews.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'mosques.js';
[$lang, $dir] = getLangAndDir();

$mainContent = '
      <div class="page-head">
        <h1>Mosques</h1>
        <p id="resultCount">Loading…</p>
      </div>

      <form class="filters" id="filtersForm">
        <div class="filter">
          <label for="f-q">Search</label>
          <input type="text" id="f-q" name="q" placeholder="Name, address…">
        </div>
        <div class="filter">
          <label for="f-city">City</label>
          <select id="f-city" name="city"><option value="">All cities</option></select>
        </div>
        <div class="filter">
          <button class="btn btn-primary btn-block" type="submit">Search</button>
          <a class="btn btn-ghost btn-block" href="mosques" style="margin-top:.4rem;">Clear</a>
        </div>
        <input type="hidden" name="lat" id="f-lat">
        <input type="hidden" name="lng" id="f-lng">
        <input type="hidden" name="radius" id="f-radius" value="50">
        <input type="hidden" name="sort" id="f-sort" value="rating">
      </form>

      <div class="result-bar">
        <button class="btn btn-outline btn-sm" id="nearMeBtn" type="button" title="Use your location to find nearby results">📍 Near me</button>
        <span id="resultBarSpacer"></span>
        <div class="view-toggle" id="viewToggle">
          <button class="view-btn active" data-view="list">List</button>
          <button class="view-btn" data-view="map">Map</button>
        </div>
      </div>

      <div class="grid" id="mosqueResults">
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
      </div>
      <div id="mapContainer" class="map-box" hidden></div>
      <div id="pagination"></div>
';

require_once __DIR__ . '/templates/base.php';
