/**
 * Homepage logic
 */
'use strict';

/* ============================================================
 * Colored category illustrations (Yelp-style tiles).
 * Only slugs with an asset here render a tile image; anything
 * else falls back to the inline line icon.
 * ============================================================ */
const CAT_ILLUSTRATIONS = {
  restaurants: 'assets/img/cats/restaurants.svg',
  shopping: 'assets/img/cats/shopping.svg',
  services: 'assets/img/cats/services.svg',
  'health-medical': 'assets/img/cats/health-medical.svg',
  education: 'assets/img/cats/education.svg',
  automotive: 'assets/img/cats/automotive.svg',
};

/* ============================================================
 * Hero Carousel
 * ============================================================ */
const SWIPE_THRESHOLD = 50;

function initHeroCarousel() {
  const carousel = document.getElementById('heroCarousel');
  if (!carousel) return;

  const track = carousel.querySelector('.hero-carousel-track');
  const slides = carousel.querySelectorAll('.hero-carousel-slide');
  const prevBtn = carousel.querySelector('.hero-carousel-prev');
  const nextBtn = carousel.querySelector('.hero-carousel-next');
  const dotsContainer = carousel.querySelector('.hero-carousel-dots');

  if (!track || slides.length <= 1) {
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
    if (dotsContainer) dotsContainer.style.display = 'none';
    return;
  }

  let currentIndex = 0;
  let autoAdvanceTimer = null;
  const AUTO_ADVANCE_INTERVAL = 5000;

  function stopAutoAdvance() {
    if (autoAdvanceTimer) {
      clearInterval(autoAdvanceTimer);
      autoAdvanceTimer = null;
    }
  }

  function startAutoAdvance() {
    // Respect prefers-reduced-motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    autoAdvanceTimer = setInterval(nextSlide, AUTO_ADVANCE_INTERVAL);
  }

  // Create dot indicators
  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'hero-carousel-dot' + (i === 0 ? ' active' : '');
    dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
    dot.setAttribute('aria-selected', i === 0);
    dot.addEventListener('click', () => goToSlide(i));
    dotsContainer.appendChild(dot);
  });

  const dots = dotsContainer.querySelectorAll('.hero-carousel-dot');

  function updateCarousel() {
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    dots.forEach((dot, i) => {
      const active = i === currentIndex;
      dot.classList.toggle('active', active);
      dot.setAttribute('aria-selected', active);
    });
    // Move focus to current slide caption for screen readers
    const activeSlide = slides[currentIndex];
    if (activeSlide) {
      const caption = activeSlide.querySelector('.slide-caption h3');
      if (caption) caption.focus({ preventScroll: true });
    }
  }

  function goToSlide(index) {
    currentIndex = (index + slides.length) % slides.length;
    updateCarousel();
    resetAutoAdvance();
  }

  function nextSlide() {
    goToSlide(currentIndex + 1);
  }

  function prevSlide() {
    goToSlide(currentIndex - 1);
  }

  function resetAutoAdvance() {
    stopAutoAdvance();
    startAutoAdvance();
  }

  // Event listeners
  prevBtn.addEventListener('click', prevSlide);
  nextBtn.addEventListener('click', nextSlide);

  // Pause on hover
  carousel.addEventListener('mouseenter', stopAutoAdvance);
  carousel.addEventListener('mouseleave', startAutoAdvance);

  // Touch/swipe support with scroll detection
  let touchStartX = 0;
  let touchStartY = 0;
  let isScrolling = false;
  carousel.addEventListener('touchstart', (e) => {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
    isScrolling = false;
    stopAutoAdvance();
  }, { passive: true });

  carousel.addEventListener('touchmove', (e) => {
    const touchY = e.touches[0].clientY;
    if (Math.abs(touchY - touchStartY) > 10) {
      isScrolling = true;
    }
  }, { passive: true });

  carousel.addEventListener('touchend', (e) => {
    if (isScrolling) { startAutoAdvance(); return; }
    const touchEndX = e.changedTouches[0].clientX;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > SWIPE_THRESHOLD) {
      if (diff > 0) nextSlide();
      else prevSlide();
    }
    startAutoAdvance();
  }, { passive: true });

  // Keyboard navigation
  carousel.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      prevSlide();
    } else if (e.key === 'ArrowRight') {
      e.preventDefault();
      nextSlide();
    }
  });

  // Start auto-advance
  startAutoAdvance();

  // Handle visibility change (pause when tab not visible)
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stopAutoAdvance();
    else startAutoAdvance();
  });

  // Cleanup on page unload
  window.addEventListener('beforeunload', stopAutoAdvance, { once: true });
}

async function loadHeroCarousel() {
  const carousel = document.getElementById('heroCarousel');
  if (!carousel) return;
  
  try {
    const photos = await api('api/activity.php?photos=1');
    if (!photos || !photos.length) return;
    
    if (!carousel.querySelector('.hero-carousel-track')) {
      // Create carousel structure
      carousel.innerHTML = `
        <div class="hero-carousel-track"></div>
        <button class="hero-carousel-nav hero-carousel-prev" aria-label="Previous slide" type="button">‹</button>
        <button class="hero-carousel-nav hero-carousel-next" aria-label="Next slide" type="button">›</button>
        <div class="hero-carousel-dots" aria-label="Carousel indicators"></div>
      `;
    }
    
    // Apply ARIA attributes unconditionally
    carousel.setAttribute('aria-live', 'polite');
    carousel.setAttribute('aria-atomic', 'true');
    carousel.setAttribute('aria-roledescription', 'carousel');
    carousel.setAttribute('aria-label', 'Hero image carousel');
    
    const trackEl = carousel.querySelector('.hero-carousel-track');
    trackEl.innerHTML = photos
      .filter(p => p.photo_path)
      .map((p, i) => `
      <div class="hero-carousel-slide" id="hero-slide-${i}" aria-label="Slide ${i + 1}">
        <img src="${esc(p.photo_path)}" alt="${esc(p.caption || p.listing_name || '')}" loading="${i === 0 ? 'eager' : 'lazy'}">
        <div class="slide-caption">
          <h3 tabindex="-1">${esc(p.listing_name || '')}</h3>
          <p>${esc((p.listing_type || '').charAt(0).toUpperCase() + (p.listing_type || '').slice(1))}${p.user_name ? ' · ' + esc(p.user_name) : ''}</p>
        </div>
      </div>
    `).join('');
    
    // Initialize carousel after slides are rendered
    initHeroCarousel();
  } catch (e) {
    console.warn('Could not load hero carousel:', e);
  }
}

window.appReady.then(async () => {
  /* ---- Hero Carousel ---- */
  await loadHeroCarousel();

  /* ---- sponsored strip (homepage header placement) ---- */
  const strip = document.getElementById('sponsoredStrip');
  if (strip) {
    try {
      const res = await api('api/ads.php?placement=homepage_header');
      const ads = (res && res.ads) || [];
      if (ads.length) {
        strip.innerHTML = ads.map(ad => { recordAdImpression(ad.id); return sponsoredBanner(ad); }).join('');
      }
    } catch (e) { /* no ads — fine */ }
  }

  /* ---- categories ---- */
  const chipsEl = document.getElementById('categoryChips');
  try {
    const cats = await api('api/categories.php?type=business');
    chipsEl.innerHTML = cats.map(c => {
      const ill = CAT_ILLUSTRATIONS[c.slug];
      const art = ill
        ? `<img class="cat-icon cat-ill" src="${ill}" alt="" width="64" height="64" loading="lazy">`
        : `<span class="cat-icon">${icon(c.icon || 'grid', 22)}</span>`;
      return `
      <a class="category-chip" href="businesses?category=${encodeURIComponent(c.slug)}">
        ${art}
        <span>${esc(lang === 'ar' && c.name_ar ? c.name_ar : lang === 'sw' && c.name_sw ? c.name_sw : c.name)}</span>
      </a>`;
    }).join('');
  } catch (e) {
    chipsEl.innerHTML = emptyState('Could not load categories', e.message);
  }

  /* ---- featured businesses ---- */
  const bizEl = document.getElementById('featuredBusinesses');
  try {
    const featured = await api('api/businesses.php?featured=1');
    bizEl.innerHTML = featured.length
      ? featured.map(businessTile).join('')
      : emptyState('No featured businesses yet', 'Be the first to add one!', '<a class="btn btn-primary btn-sm" href="register">Add a business</a>');
  } catch (e) {
    bizEl.innerHTML = emptyState('Could not load businesses', e.message);
  }

  /* ---- top mosques ---- */
  const mosqueEl = document.getElementById('topMosques');
  try {
    const mosques = await api('api/mosques.php?top=1');
    mosqueEl.innerHTML = mosques.length
      ? mosques.map(mosqueTile).join('')
      : emptyState('No mosques listed yet', 'Check back soon.');
  } catch (e) {
    mosqueEl.innerHTML = emptyState('Could not load mosques', e.message);
  }

  /* ---- top fundis ---- */
  const fundiEl = document.getElementById('topFundis');
  try {
    const fundis = await api('api/fundis.php?top=1');
    fundiEl.innerHTML = fundis.length
      ? fundis.map(fundiTile).join('')
      : emptyState('No fundis listed yet', 'Check back soon.');
  } catch (e) {
    fundiEl.innerHTML = emptyState('Could not load fundis', e.message);
  }

  /* ---- active causes ---- */
  const charityEl = document.getElementById('topCharities');
  if (charityEl) {
    try {
      const charities = await api('api/charities.php?top=1');
      charityEl.innerHTML = charities.length
        ? charities.map(charityTile).join('')
        : emptyState('No causes yet', 'Check back soon.');
    } catch (e) {
      charityEl.innerHTML = emptyState('Could not load causes', e.message);
    }
  }

  /* ---- recent activity feed ---- */
  const activityEl = document.getElementById('recentActivity');
  if (activityEl) {
    try {
      const feed = await api('api/activity.php');
      activityEl.innerHTML = feed.length
        ? feed.map(activityItem).join('')
        : '<p class="muted center" style="padding:1.2rem 0;">No activity yet — be the first to write a review!</p>';
    } catch (e) {
      activityEl.innerHTML = '<p class="muted">Could not load activity.</p>';
    }
  }

  /* ---- search autocomplete ---- */
  wireAutocomplete(document.getElementById('hero-q'), document.getElementById('heroSuggest'));

  /* ---- stats (best-effort from list endpoints) ---- */
  try {
    const [biz, mosques, fundis] = await Promise.all([
      api('api/businesses.php'),
      api('api/mosques.php'),
      api('api/fundis.php'),
    ]);
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('statBiz', biz.total.toLocaleString());
    set('statMosque', mosques.total.toLocaleString());
    set('statFundi', fundis.total.toLocaleString());
    set('statReviews', (biz.items.reduce((s, x) => s + (x.review_count || 0), 0)).toLocaleString());
  } catch (e) { /* stats stay as placeholders */ }
});