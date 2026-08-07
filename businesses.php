<?php
/**
 * Business directory listing page — unified PHP entry point
 * Replaces businesses.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Businesses — Ummah Directory';
$pageDescription = 'Search halal businesses by category, city and rating.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'businesses.js';
[$lang, $dir] = getLangAndDir();

$mainContent = '
      <nav class="breadcrumb" id="breadcrumb" aria-label="Breadcrumb"></nav>

      <div class="page-head">
        <h1>Businesses</h1>
        <p id="resultCount">Loading…</p>
      </div>

      <form class="filters" id="filtersForm">
        <div class="filter autocomplete-wrap">
          <label for="f-q">Search</label>
          <input type="text" id="f-q" name="q" placeholder="Restaurant, butcher, pharmacy…" autocomplete="off">
          <div class="autocomplete" id="bizSuggest"></div>
        </div>
        <div class="filter">
          <label for="f-category">Category</label>
          <select id="f-category" name="category">
            <option value="">All categories</option>
          </select>
        </div>
        <div class="filter">
          <label for="f-city">City</label>
          <select id="f-city" name="location">
            <option value="">All cities</option>
          </select>
        </div>
        <div class="filter">
          <label for="f-price">Price</label>
          <select id="f-price" name="price">
            <option value="">Any</option>
            <option value="$">$ Inexpensive</option>
            <option value="$$">$$ Moderate</option>
            <option value="$$$">$$$ Expensive</option>
            <option value="$$$$">$$$$ Premium</option>
          </select>
        </div>
        <div class="filter">
          <label for="f-rating">Min rating</label>
          <select id="f-rating" name="min_rating">
            <option value="0">Any</option>
            <option value="4">4+ stars</option>
            <option value="3">3+ stars</option>
          </select>
        </div>
        <div class="filter">
          <label for="f-sort">Sort by</label>
          <select id="f-sort" name="sort">
            <option value="rating">Top rated</option>
            <option value="reviews">Most reviewed</option>
            <option value="name">Name (A–Z)</option>
            <option value="distance">Distance</option>
          </select>
        </div>
        <div class="filter checkbox-filter-wrap">
          <label class="checkbox-filter"><input type="checkbox" name="hours" value="open" id="f-open"> Open now</label>
        </div>
        <input type="hidden" name="lat" id="f-lat">
        <input type="hidden" name="lng" id="f-lng">
        <input type="hidden" name="radius" id="f-radius" value="50">
        <div class="filter">
          <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
          <a class="btn btn-ghost btn-block" href="businesses" style="margin-top:.4rem;">Clear</a>
        </div>
      </form>

      <div class="result-bar">
        <button class="btn btn-outline btn-sm" id="nearMeBtn" type="button" title="Use your location to find nearby results">📍 Near me</button>
        <span id="resultBarSpacer"></span>
        <div class="view-toggle" id="viewToggle">
          <button class="view-btn active" data-view="list">List</button>
          <button class="view-btn" data-view="map">Map</button>
        </div>
      </div>

      <div class="listing-layout" id="businessResults">
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
        <div class="card listing-card skeleton"><div class="skeleton-media"></div><div class="card-body"><div class="sk-line w60"></div><div class="sk-line w40"></div><div class="sk-line w80"></div></div></div>
      </div>
      <div id="mapContainer" class="map-box" hidden></div>
      <div id="pagination"></div>
';

require_once __DIR__ . '/templates/base.php';
