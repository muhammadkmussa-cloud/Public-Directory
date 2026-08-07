<?php
/**
 * Homepage — unified PHP entry point
 * Replaces index.html with server-rendered meta tags via base.php
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/TemplateHelpers.php';

$pageTitle = 'Ummah Directory — Discover Muslim-Friendly Businesses, Mosques & Fundis';
$pageDescription = 'Find trusted businesses, mosques, fundis and charities in your community. Read reviews, view prayer times and connect with local Muslim services.';
$pageUrl = getCurrentPageUrl();
$pageImage = 'https://yourdomain.com/assets/img/logo.png';
$pageType = 'website';
$pageScript = 'home.js';
[$lang, $dir] = getLangAndDir();
$rawMain = true;

$mainContent = '
    <!-- Hero — Yelp-style white with Find/Near search -->
    <section class="hero">
      <div class="container">
        <h1 data-i18n="Discover the Best in Your Community">The best halal businesses, mosques &amp; fundis in your community</h1>
        <p>Find trusted, reviewed places — from restaurants to skilled workers.</p>

        <form class="yelp-search" id="heroSearch" action="businesses" method="get">
          <div class="search-field autocomplete-wrap">
            <label for="hero-q">Find</label>
            <input id="hero-q" type="text" name="q" placeholder="restaurants, plumbers, mosques…" aria-label="What are you looking for?" autocomplete="off">
            <div class="autocomplete" id="heroSuggest"></div>
          </div>
          <div class="search-field">
            <label for="hero-near">Near</label>
            <input id="hero-near" type="text" name="location" placeholder="Nairobi, Mombasa, Kisumu…" aria-label="City or area">
          </div>
          <button class="btn btn-primary btn-search" type="submit">Search</button>
        </form>

        <div class="hero-stats" id="heroStats">
          <div><b id="statBiz">–</b>Halal businesses</div>
          <div><b id="statMosque">–</b>Mosques</div>
          <div><b id="statFundi">–</b>Trusted fundis</div>
          <div><b id="statReviews">–</b>Reviews</div>
        </div>
      </div>
    </section>

    <!-- Hero Carousel -->
    <div class="container hero-carousel" id="heroCarousel" aria-label="Featured places carousel"></div>

    <!-- Sponsored strip -->
    <div class="container" id="sponsoredStrip"></div>

    <!-- Categories -->
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 data-i18n="Explore by category">Explore by category</h2>
            <p class="sub">Jump straight to what you need</p>
          </div>
        </div>
        <div class="category-grid" id="categoryChips"><!-- filled by home.js --></div>
      </div>
    </section>

    <!-- Featured businesses -->
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 data-i18n="Featured businesses">Featured businesses</h2>
            <p class="sub">Loved by the community</p>
          </div>
          <a class="btn btn-outline btn-sm" href="businesses">View all</a>
        </div>
        <div class="grid" id="featuredBusinesses"><!-- filled by home.js --></div>
      </div>
    </section>

    <!-- Top mosques -->
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 data-i18n="Mosques near you">Mosques near you</h2>
            <p class="sub">Prayer times, facilities &amp; reviews</p>
          </div>
          <a class="btn btn-outline btn-sm" href="mosques">View all</a>
        </div>
        <div class="grid" id="topMosques"><!-- filled by home.js --></div>
      </div>
    </section>

    <!-- Top fundis -->
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 data-i18n="Top rated fundis">Top rated fundis</h2>
            <p class="sub">Verified skilled workers</p>
          </div>
          <a class="btn btn-outline btn-sm" href="fundis">View all</a>
        </div>
        <div class="grid" id="topFundis"><!-- filled by home.js --></div>
      </div>
    </section>

    <!-- Active causes -->
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 data-i18n="Active causes">Active causes</h2>
            <p class="sub">Zakat, sadaqah &amp; relief campaigns</p>
          </div>
          <a class="btn btn-outline btn-sm" href="charities">View all</a>
        </div>
        <div class="grid" id="topCharities"><!-- filled by home.js --></div>
      </div>
    </section>

    <!-- Recent Activity (Yelp\'s signature homepage feed) -->
    <section class="section">
      <div class="container">
        <div class="section-head">
          <div>
            <h2 data-i18n="Recent activity">Recent activity</h2>
            <p class="sub">What the community is saying</p>
          </div>
        </div>
        <div class="panel" id="recentActivity"><p class="muted">Loading…</p></div>
      </div>
    </section>

    <!-- CTA -->
    <section class="section">
      <div class="container">
        <div class="panel center" style="background:var(--bg-soft);border:1px solid var(--border);">
          <h2 style="font-size:1.4rem;margin-bottom:.4rem;" data-i18n="Own a business or a skill to share?">Own a business or a skill to share?</h2>
          <p class="muted" style="margin-bottom:1.1rem;">Claim your listing, respond to reviews, and let the community find you.</p>
          <a class="btn btn-primary" href="register">Create a free account</a>
        </div>
      </div>
    </section>
';

require_once __DIR__ . '/templates/base.php';
