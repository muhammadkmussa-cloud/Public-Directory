/**
 * Ummah Directory — Zero-Dependency Lightbox Gallery
 * Displays full-screen image preview with next/prev navigation, captions, and keyboard controls.
 */

'use strict';

(function () {
  let lightboxModal = null;
  let currentImages = [];
  let currentIndex = 0;

  function createLightboxDOM() {
    if (lightboxModal) return;

    lightboxModal = document.createElement('div');
    lightboxModal.id = 'lightboxModal';
    lightboxModal.className = 'lightbox-modal';
    lightboxModal.style.cssText = `
      position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(0, 0, 0, 0.9); z-index: 99999; display: none;
      align-items: center; justify-content: center; flex-direction: column;
      user-select: none;
    `;

    lightboxModal.innerHTML = `
      <button id="lbClose" style="position:absolute;top:20px;right:25px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;line-height:1;z-index:100000;" aria-label="Close">&times;</button>
      <button id="lbPrev" style="position:absolute;left:20px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:2rem;padding:0.6rem 1rem;border-radius:50%;cursor:pointer;z-index:100000;" aria-label="Previous">&lsaquo;</button>
      <button id="lbNext" style="position:absolute;right:20px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:2rem;padding:0.6rem 1rem;border-radius:50%;cursor:pointer;z-index:100000;" aria-label="Next">&rsaquo;</button>
      <div style="max-width:90vw;max-height:80vh;display:flex;align-items:center;justify-content:center;">
        <img id="lbImage" src="" alt="" style="max-width:100%;max-height:80vh;object-fit:contain;border-radius:4px;box-shadow:0 4px 20px rgba(0,0,0,0.5);">
      </div>
      <div style="color:#eee;margin-top:1rem;text-align:center;">
        <div id="lbCaption" style="font-size:0.95rem;font-weight:600;"></div>
        <div id="lbCounter" style="font-size:0.8rem;opacity:0.7;margin-top:0.2rem;"></div>
      </div>
    `;

    document.body.appendChild(lightboxModal);

    document.getElementById('lbClose').addEventListener('click', closeLightbox);
    document.getElementById('lbPrev').addEventListener('click', prevImage);
    document.getElementById('lbNext').addEventListener('click', nextImage);

    lightboxModal.addEventListener('click', (e) => {
      if (e.target === lightboxModal) closeLightbox();
    });

    document.addEventListener('keydown', (e) => {
      if (!lightboxModal || lightboxModal.style.display !== 'flex') return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') prevImage();
      if (e.key === 'ArrowRight') nextImage();
    });
  }

  function updateLightbox() {
    if (!currentImages.length) return;
    const imgEl = document.getElementById('lbImage');
    const capEl = document.getElementById('lbCaption');
    const cntEl = document.getElementById('lbCounter');

    const item = currentImages[currentIndex];
    const src = typeof item === 'string' ? item : (item.src || item.photo_path);
    const caption = typeof item === 'object' ? (item.caption || item.alt || '') : '';

    imgEl.src = src;
    imgEl.alt = caption;
    capEl.textContent = caption;
    cntEl.textContent = `${currentIndex + 1} / ${currentImages.length}`;
  }

  function openLightbox(images, index = 0) {
    if (!images || !images.length) return;
    createLightboxDOM();
    currentImages = images;
    currentIndex = Math.max(0, Math.min(index, images.length - 1));
    updateLightbox();
    lightboxModal.style.display = 'flex';
  }

  function closeLightbox() {
    if (lightboxModal) lightboxModal.style.display = 'none';
  }

  function prevImage() {
    if (!currentImages.length) return;
    currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
    updateLightbox();
  }

  function nextImage() {
    if (!currentImages.length) return;
    currentIndex = (currentIndex + 1) % currentImages.length;
    updateLightbox();
  }

  // Global delegation handler for elements with data-lightbox or inside .photo-grid / .review-photos
  document.addEventListener('click', (e) => {
    const targetImg = e.target.closest('.photo-grid img, .review-photos img, [data-lightbox]');
    if (!targetImg) return;

    const container = targetImg.closest('.photo-grid, .review-photos') || targetImg.parentElement;
    const allImgs = Array.from(container.querySelectorAll('img, [data-lightbox]'));

    if (allImgs.length > 0) {
      const imgList = allImgs.map(img => ({
        src: img.dataset.fullSrc || img.src,
        caption: img.alt || img.title || ''
      }));
      const idx = allImgs.indexOf(targetImg);
      openLightbox(imgList, idx >= 0 ? idx : 0);
    } else {
      openLightbox([{ src: targetImg.dataset.fullSrc || targetImg.src, caption: targetImg.alt || '' }], 0);
    }
  });

  window.openLightbox = openLightbox;
})();
