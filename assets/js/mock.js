/**
 * Ummah Directory — DEMO DATA LAYER (mock API)
 * Used ONLY when the PHP backend can't be reached (e.g. static preview).
 * In production the real api/*.php endpoints are used and this file is never hit.
 * Shapes mirror the real API responses exactly.
 */
'use strict';

window.mockApi = (function () {

  const BUSINESSES = [
    { id: 1, name: 'Al-Barakah Restaurant', slug: 'al-barakah-restaurant', category_name: 'Restaurants', city: 'Nairobi', region: 'Nairobi County', price_range: '$$', short_description: 'Authentic halal Swahili & Arabic cuisine in the heart of the city.', is_verified: 1, is_open: 1, rating_average: 4.5, review_count: 2, checkin_count: 34, primary_photo: 'assets/img/sample/restaurant-1.svg', latitude: -1.2864, longitude: 36.8231 },
    { id: 2, name: 'Baitul Aman Halal Butcher', slug: 'baitul-aman-halal-butcher', category_name: 'Shopping', city: 'Mombasa', region: 'Mombasa County', price_range: '$$', short_description: 'Fresh certified halal meat and groceries.', is_verified: 1, rating_average: 5.0, review_count: 1, checkin_count: 18, primary_photo: 'assets/img/sample/butcher.svg', latitude: -4.0435, longitude: 39.6682 },
    { id: 3, name: 'Noor Pharmacy', slug: 'noor-pharmacy', category_name: 'Health & Medical', city: 'Nairobi', region: 'Nairobi County', price_range: '$$', short_description: 'Your trusted community pharmacy.', is_verified: 1, rating_average: 4.0, review_count: 1, checkin_count: 9, primary_photo: 'assets/img/sample/pharmacy.svg', latitude: -1.2921, longitude: 36.8219 },
    { id: 4, name: 'Iqra Bookstore & Islamic Gifts', slug: 'iqra-bookstore', category_name: 'Education', city: 'Nairobi', region: 'Nairobi County', price_range: '$', short_description: 'Books, Qurans and Islamic gifts for the whole family.', is_verified: 1, rating_average: 5.0, review_count: 1, checkin_count: 12, primary_photo: 'assets/img/sample/bookstore.svg', latitude: -1.29, longitude: 36.825 },
    { id: 5, name: 'Al-Salam Travel & Tours', slug: 'al-salam-travel', category_name: 'Services', city: 'Mombasa', region: 'Mombasa County', price_range: '$$$', short_description: 'Hajj, Umrah and holiday packages you can trust.', is_verified: 1, rating_average: 5.0, review_count: 1, checkin_count: 27, primary_photo: 'assets/img/sample/travel.svg', latitude: -4.0435, longitude: 39.6682 },
    { id: 6, name: 'Green Bites Halal Cafe', slug: 'green-bites-cafe', category_name: 'Restaurants', city: 'Kisumu', region: 'Kisumu County', price_range: '$', short_description: 'Fresh juices, coffee & light bites in a cozy space.', is_verified: 1, rating_average: 4.0, review_count: 1, checkin_count: 5, primary_photo: 'assets/img/sample/restaurant-2.svg', latitude: -0.1022, longitude: 34.7617 },
  ];

  const BUSINESS_DETAILS = {
    1: { id: 1, name: 'Al-Barakah Restaurant', slug: 'al-barakah-restaurant', category_name: 'Restaurants', price_range: '$$', description: 'Family-friendly halal restaurant serving authentic Swahili and Arabic cuisine. Famous for our biryani, samosas, and fresh mandazi. Separate family seating available.', city: 'Nairobi', region: 'Nairobi County', country: 'Kenya', address: 'Moi Avenue, Next to City Mall', phone: '+254 722 111 222', whatsapp: '+254722111222', email: 'info@albarakah.co.ke', website: 'https://albarakah.co.ke', is_verified: 1, is_open: 1, rating_average: 4.5, review_count: 2, checkin_count: 34, latitude: -1.2864, longitude: 36.8231, opening_hours: { monday: '08:00 - 22:00', tuesday: '08:00 - 22:00', wednesday: '08:00 - 22:00', thursday: '08:00 - 22:00', friday: '14:00 - 23:00', saturday: '08:00 - 23:00', sunday: '08:00 - 22:00' }, amenities: ['Halal Certified', 'Free Wi-Fi', 'Parking', 'Family Seating', 'Delivery'] },
    2: { id: 2, name: 'Baitul Aman Halal Butcher', slug: 'baitul-aman-halal-butcher', category_name: 'Shopping', price_range: '$$', description: 'Certified halal butcher offering fresh beef, goat, and chicken. We also stock a wide range of halal groceries and spices.', city: 'Mombasa', region: 'Mombasa County', country: 'Kenya', address: 'Digo Road, Opposite Mwembe Tayari', phone: '+254 733 222 333', whatsapp: '+254733222333', email: 'baitulaman@gmail.com', website: '', is_verified: 1, is_open: 1, rating_average: 5.0, review_count: 1, checkin_count: 18, latitude: -4.0435, longitude: 39.6682, opening_hours: { monday: '07:00 - 19:00', tuesday: '07:00 - 19:00', wednesday: '07:00 - 19:00', thursday: '07:00 - 19:00', friday: '07:00 - 19:00', saturday: '07:00 - 20:00', sunday: '08:00 - 18:00' }, amenities: ['Halal Certified', 'Fresh Daily'] },
    3: { id: 3, name: 'Noor Pharmacy', slug: 'noor-pharmacy', category_name: 'Health & Medical', price_range: '$$', description: 'Community pharmacy offering prescription medicines, wellness products, and free blood-pressure checks. Licensed pharmacists on duty.', city: 'Nairobi', region: 'Nairobi County', country: 'Kenya', address: 'Kenyatta Avenue, Pioneer House', phone: '+254 711 333 444', whatsapp: '', email: 'hello@noorpharmacy.co.ke', website: '', is_verified: 1, is_open: 1, rating_average: 4.0, review_count: 1, checkin_count: 9, latitude: -1.2921, longitude: 36.8219, opening_hours: { monday: '08:00 - 20:00', tuesday: '08:00 - 20:00', wednesday: '08:00 - 20:00', thursday: '08:00 - 20:00', friday: '08:00 - 20:00', saturday: '09:00 - 18:00', sunday: 'Closed' }, amenities: ['Free BP Check', 'Insurance Accepted'] },
    4: { id: 4, name: 'Iqra Bookstore & Islamic Gifts', slug: 'iqra-bookstore', category_name: 'Education', price_range: '$', description: 'Quran copies, Islamic books in English, Kiswahili and Arabic, prayer mats, hijabs, and gifts for all occasions.', city: 'Nairobi', region: 'Nairobi County', country: 'Kenya', address: 'Mfangano Street', phone: '+254 700 444 555', whatsapp: '+254700444555', email: 'iqrabooks@gmail.com', website: '', is_verified: 1, is_open: 1, rating_average: 5.0, review_count: 1, checkin_count: 12, latitude: -1.29, longitude: 36.825, opening_hours: { monday: '09:00 - 18:00', tuesday: '09:00 - 18:00', wednesday: '09:00 - 18:00', thursday: '09:00 - 18:00', friday: '09:00 - 18:00', saturday: '09:00 - 18:00', sunday: 'Closed' }, amenities: ['Gift Wrapping', 'Kids Section'] },
    5: { id: 5, name: 'Al-Salam Travel & Tours', slug: 'al-salam-travel', category_name: 'Services', price_range: '$$$', description: 'Hajj and Umrah packages, domestic safaris, and flight bookings. Our team has guided over 2,000 pilgrims.', city: 'Mombasa', region: 'Mombasa County', country: 'Kenya', address: 'Nkrumah Road, Makuti House', phone: '+254 755 555 666', whatsapp: '+254755555666', email: 'bookings@alsalamtravel.co.ke', website: 'https://alsalamtravel.co.ke', is_verified: 1, is_open: 1, rating_average: 5.0, review_count: 1, checkin_count: 27, latitude: -4.0435, longitude: 39.6682, opening_hours: { monday: '08:30 - 17:30', tuesday: '08:30 - 17:30', wednesday: '08:30 - 17:30', thursday: '08:30 - 17:30', friday: '08:30 - 12:30', saturday: 'Closed', sunday: 'Closed' }, amenities: ['Hajj & Umrah', 'Flight Bookings', 'Visa Help'] },
    6: { id: 6, name: 'Green Bites Halal Cafe', slug: 'green-bites-cafe', category_name: 'Restaurants', price_range: '$', description: 'Cozy halal cafe with fresh juices, smoothies, coffee, and light bites. Free Wi-Fi and a quiet study corner.', city: 'Kisumu', region: 'Kisumu County', country: 'Kenya', address: 'Oginga Odinga Street', phone: '+254 729 666 777', whatsapp: '+254729666777', email: 'greenbites@gmail.com', website: '', is_verified: 1, is_open: 1, rating_average: 4.0, review_count: 1, checkin_count: 5, latitude: -0.1022, longitude: 34.7617, opening_hours: { monday: '07:00 - 20:00', tuesday: '07:00 - 20:00', wednesday: '07:00 - 20:00', thursday: '07:00 - 20:00', friday: '07:00 - 20:00', saturday: '08:00 - 21:00', sunday: '08:00 - 18:00' }, amenities: ['Free Wi-Fi', 'Halal Certified', 'Study Corner'] },
  };

  const MOSQUES = [
    { id: 1, name: 'Jamia Mosque Nairobi', slug: 'jamia-mosque-nairobi', city: 'Nairobi', address: 'Banda Street, CBD', is_verified: 1, rating_average: 5.0, review_count: 1, phone: '+254 20 222 101', latitude: -1.2864, longitude: 36.8231, primary_photo: 'assets/img/sample/mosque-1.svg' },
    { id: 2, name: 'Mombasa Central Mosque', slug: 'mombasa-central-mosque', city: 'Mombasa', address: 'Old Town, Fort Jesus Road', is_verified: 1, rating_average: 5.0, review_count: 1, phone: '+254 41 222 202', latitude: -4.0667, longitude: 39.6616, primary_photo: 'assets/img/sample/mosque-2.svg' },
    { id: 3, name: 'Masjid Noor', slug: 'masjid-noor', city: 'Nairobi', address: 'Parklands, Third Avenue', is_verified: 1, rating_average: 4.0, review_count: 1, phone: '+254 733 000 111', latitude: -1.3053, longitude: 36.8304, primary_photo: 'assets/img/sample/mosque-3.svg' },
    { id: 4, name: 'Riyadha Mosque', slug: 'riyadha-mosque', city: 'Lamu', address: 'Harambee Avenue, Lamu Old Town', is_verified: 1, rating_average: 0, review_count: 0, phone: '+254 42 633 303', latitude: -2.2697, longitude: 40.9021, primary_photo: 'assets/img/sample/mosque-4.svg' },
  ];

  const MOSQUE_DETAILS = {
    1: { id: 1, name: 'Jamia Mosque Nairobi', slug: 'jamia-mosque-nairobi', description: "One of Nairobi's most historic mosques, located in the city centre. Hosts daily prayers, jumuah, Quran classes and community iftar during Ramadan.", city: 'Nairobi', region: 'Nairobi County', address: 'Banda Street, CBD', phone: '+254 20 222 101', email: 'info@jamiamosque.or.ke', is_verified: 1, rating_average: 5.0, review_count: 1, capacity: 5000, latitude: -1.2864, longitude: 36.8231, facilities: { women_section: true, wudu: true, parking: false, wheelchair: true, quran_classes: true, ramadan_iftar: true }, imam_name: 'Sheikh Abdullahi Mohamed' },
    2: { id: 2, name: 'Mombasa Central Mosque', slug: 'mombasa-central-mosque', description: 'Historic seaside mosque in Old Town Mombasa with a beautiful coral-stone minaret. Friday lectures in Kiswahili and Arabic.', city: 'Mombasa', region: 'Mombasa County', address: 'Old Town, Fort Jesus Road', phone: '+254 41 222 202', email: '', is_verified: 1, rating_average: 5.0, review_count: 1, capacity: 2500, latitude: -4.0667, longitude: 39.6616, facilities: { women_section: true, wudu: true, parking: false, wheelchair: false, quran_classes: true, ramadan_iftar: true }, imam_name: 'Sheikh Ali Abdalla' },
    3: { id: 3, name: 'Masjid Noor', slug: 'masjid-noor', description: 'Neighbourhood mosque with a friendly community feel. Daily classes for children and an active youth group.', city: 'Nairobi', region: 'Nairobi County', address: 'Parklands, Third Avenue', phone: '+254 733 000 111', email: '', is_verified: 1, rating_average: 4.0, review_count: 1, capacity: 800, latitude: -1.3053, longitude: 36.8304, facilities: { women_section: true, wudu: true, parking: true, wheelchair: true, quran_classes: true, ramadan_iftar: true }, imam_name: 'Imam Yusuf Otieno' },
    4: { id: 4, name: 'Riyadha Mosque', slug: 'riyadha-mosque', description: 'Historic 19th-century mosque on Lamu Island, a UNESCO World Heritage site. Famous for the Maulidi festival.', city: 'Lamu', region: 'Lamu County', address: 'Harambee Avenue, Lamu Old Town', phone: '+254 42 633 303', email: '', is_verified: 1, rating_average: 0, review_count: 0, capacity: 1200, latitude: -2.2697, longitude: 40.9021, facilities: { women_section: false, wudu: true, parking: false, wheelchair: false, quran_classes: true, ramadan_iftar: true }, imam_name: 'Sheikh Ahmed Badawy' },
  };

  const FUNDIS = [
    { id: 1, full_name: 'Abdullahi Said', profession: 'Plumber', latitude: -4.0435, longitude: 39.6682, years_experience: 12, city: 'Mombasa', region: 'Mombasa County', is_verified: 1, rating_average: 5.0, review_count: 1, hourly_rate_min: 500, hourly_rate_max: 1500, skills: ['Plumbing', 'Water Heaters', 'Drainage', 'Bathroom Fitting', 'Emergency Repairs'], profile_photo: '', phone: '+254 712 888 999', whatsapp: '+254712888999' },
    { id: 2, full_name: 'Fatuma Ali', profession: 'Tailor', latitude: -1.2864, longitude: 36.8231, years_experience: 8, city: 'Nairobi', region: 'Nairobi County', is_verified: 1, rating_average: 5.0, review_count: 1, hourly_rate_min: 300, hourly_rate_max: 800, skills: ['Tailoring', 'Dress Making', 'Suits', 'School Uniforms', 'Kanga & Kitenge'], profile_photo: '', phone: '+254 701 234 567', whatsapp: '+254701234567' },
    { id: 3, full_name: 'Musa Kiprop', profession: 'Electrician', latitude: -1.2921, longitude: 36.8219, years_experience: 15, city: 'Nairobi', region: 'Nairobi County', is_verified: 1, rating_average: 4.0, review_count: 1, hourly_rate_min: 600, hourly_rate_max: 2000, skills: ['Electrical Wiring', 'Solar Installation', 'Fault Finding', 'Industrial Electrical', 'Safety Inspections'], profile_photo: '', phone: '+254 722 345 678', whatsapp: '+254722345678' },
  ];

  const FUNDI_DETAILS = {
    1: { ...FUNDIS[0], bio: 'Certified plumber with 12 years experience in residential and commercial plumbing, water heaters, and drainage systems. Emergency call-outs available 24/7 within Mombasa.', working_hours: { monday: '08:00 - 18:00', tuesday: '08:00 - 18:00', wednesday: '08:00 - 18:00', thursday: '08:00 - 18:00', friday: '08:00 - 12:00', saturday: '09:00 - 17:00', sunday: 'Emergency Only' }, languages: ['English', 'Kiswahili'] },
    2: { ...FUNDIS[1], bio: 'Professional tailor specialising in kanga and kitenge dresses, suits, and school uniforms. Bulk orders for shops and offices welcome.', working_hours: { monday: '08:30 - 17:30', tuesday: '08:30 - 17:30', wednesday: '08:30 - 17:30', thursday: '08:30 - 17:30', friday: '08:30 - 12:30', saturday: '09:00 - 16:00', sunday: 'Closed' }, languages: ['English', 'Kiswahili'] },
    3: { ...FUNDIS[2], bio: 'Licensed electrician (EPRA certified) handling wiring, installations, solar systems, and fault finding for homes and businesses.', working_hours: { monday: '08:00 - 18:00', tuesday: '08:00 - 18:00', wednesday: '08:00 - 18:00', thursday: '08:00 - 18:00', friday: '08:00 - 12:00', saturday: '09:00 - 17:00', sunday: 'Closed' }, languages: ['English', 'Kiswahili'] },
  };

  const PORTFOLIO = {
    1: [{ id: 1, photo_path: 'assets/img/sample/fundi-plumber-1.svg', caption: 'Bathroom plumbing renovation' }, { id: 2, photo_path: 'assets/img/sample/fundi-plumber-2.svg', caption: 'Water heater installation' }],
    2: [{ id: 3, photo_path: 'assets/img/sample/fundi-tailor-1.svg', caption: 'Kitenge dress set' }],
    3: [{ id: 4, photo_path: 'assets/img/sample/fundi-electric-1.svg', caption: 'Solar panel wiring' }],
  };

  const ADS = [
    { id: 1, placement_id: 3, advertiser_id: 4, title: 'Al-Barakah Restaurant', image_path: 'assets/img/sample/restaurant-1.svg', link_url: 'business.html?id=1', html_content: 'Authentic halal Swahili & Arabic cuisine — family seating.', priority: 10, status: 'active', impressions: 1240, clicks: 86 },
    { id: 2, placement_id: 3, advertiser_id: 4, title: 'Al-Salam Travel & Tours', image_path: 'assets/img/sample/travel.svg', link_url: 'business.html?id=5', html_content: 'Hajj & Umrah packages — trusted by 2,000+ pilgrims.', priority: 5, status: 'active', impressions: 980, clicks: 54 },
    { id: 3, placement_id: 1, advertiser_id: 4, title: 'Iqra Bookstore & Islamic Gifts', image_path: 'assets/img/sample/bookstore.svg', link_url: 'business.html?id=4', html_content: 'Qurans, books & gifts for the whole family.', priority: 8, status: 'active', impressions: 2100, clicks: 130 },
    { id: 4, placement_id: 5, advertiser_id: 4, title: 'Noor Pharmacy', image_path: 'assets/img/sample/pharmacy.svg', link_url: 'business.html?id=3', html_content: 'Trusted community pharmacy — free BP checks.', priority: 6, status: 'active', impressions: 640, clicks: 31 },
    { id: 5, placement_id: 5, advertiser_id: 4, title: 'Green Bites Halal Cafe', image_path: 'assets/img/sample/restaurant-2.svg', link_url: 'business.html?id=6', html_content: 'Fresh juices & coffee — free Wi-Fi, study corner.', priority: 4, status: 'active', impressions: 412, clicks: 19 },
  ];
  const ADS_BY_PLACEMENT = { search_results: [1, 2], homepage_header: [3], homepage_sidebar: [], listing_page: [4], detail_page: [4, 5] };

  const CATEGORIES = [
    { id: 1, name: 'Restaurants', name_sw: 'Mikahawa', name_ar: 'مطاعم', slug: 'restaurants', icon: '🍽️' },
    { id: 2, name: 'Shopping', name_sw: 'Ununuzi', name_ar: 'تسوق', slug: 'shopping', icon: '🛍️' },
    { id: 3, name: 'Services', name_sw: 'Huduma', name_ar: 'خدمات', slug: 'services', icon: '🔧' },
    { id: 4, name: 'Health & Medical', name_sw: 'Afya', name_ar: 'صحة وطب', slug: 'health-medical', icon: '🏥' },
    { id: 5, name: 'Education', name_sw: 'Elimu', name_ar: 'تعليم', slug: 'education', icon: '📚' },
    { id: 6, name: 'Automotive', name_sw: 'Magari', name_ar: 'سيارات', slug: 'automotive', icon: '🚗' },
  ];

  const REVIEWS = [
    { id: 1, rating: 5, title: 'Best biryani in Nairobi!', content: 'The chicken biryani is incredible and the family seating is very private and comfortable. Staff are so welcoming. Highly recommended for iftar too.', helpful_count: 12, useful_count: 12, funny_count: 4, cool_count: 2, photos: ['assets/img/sample/restaurant-1.svg', 'assets/img/sample/fundi-plumber-2.svg'], created_at: new Date(Date.now() - 86400000 * 12).toISOString(), user: { full_name: 'Amina Hassan', profile_photo: '', contributor_level: 3, verification_badge: 'top_contributor' } },
    { id: 2, rating: 4, title: 'Great food, busy at peak hours', content: 'Delicious samosas and the mandazi are fresh all day. Gets crowded on Friday evenings - go early!', helpful_count: 5, useful_count: 5, funny_count: 8, cool_count: 1, photos: ['assets/img/sample/restaurant-2.svg'], created_at: new Date(Date.now() - 86400000 * 5).toISOString(), user: { full_name: 'Yusuf Omar', profile_photo: '', contributor_level: 2, verification_badge: 'verified' } },
  ];

  const CITIES = ['Nairobi', 'Mombasa', 'Kisumu', 'Lamu'];

  const CHARITIES = [
    { id: 1, name: 'Ihsan Foundation', slug: 'ihsan-foundation', category: 'orphan', city: 'Nairobi', is_verified: 1, logo_path: 'assets/img/sample/charity-ihsan.svg', cover_photo: 'assets/img/sample/charity-ihsan.svg', description: 'Ihsan Foundation supports orphans and vulnerable children across Kenya with education sponsorships, food baskets and guardianship programs.', phone: '+254 722 000 111', whatsapp: '+254722000111', email: 'info@ihsanfoundation.or.ke', website: 'https://ihsanfoundation.or.ke', paybill_number: '522533', raised: 313000, donors: 127 },
    { id: 2, name: 'Nuru Medical Fund', slug: 'nuru-medical-fund', category: 'health', city: 'Nairobi', is_verified: 1, logo_path: 'assets/img/sample/charity-nuru.svg', cover_photo: 'assets/img/sample/charity-nuru.svg', description: 'Nuru Medical Fund raises funds for life-saving surgeries and treatments for families who cannot afford hospital care.', phone: '+254 700 222 333', whatsapp: '+254700222333', email: 'care@nurumedical.org', website: 'https://nurumedical.org', paybill_number: '522544', raised: 890000, donors: 312 },
    { id: 3, name: 'Ummah Relief', slug: 'ummah-relief', category: 'emergency', city: 'Mombasa', is_verified: 1, logo_path: 'assets/img/sample/charity-relief.svg', cover_photo: 'assets/img/sample/charity-relief.svg', description: 'Rapid-response relief for families affected by floods, drought and emergencies — food, shelter and clean water.', phone: '+254 733 444 555', whatsapp: '+254733444555', email: 'relief@ummahrelief.org', website: 'https://ummahrelief.org', paybill_number: '522555', raised: 120000, donors: 64 },
  ];
  const CAMPAIGNS = {
    1: [
      { id: 1, title: 'Ramadan Food Baskets 2026', description: 'Provide a 30-day food basket to 500 families this Ramadan.', goal_amount: 500000, raised: 215000, donors: 86, progress: 43, end_date: new Date(Date.now() + 86400000 * 20).toISOString().slice(0,10) },
      { id: 2, title: 'Orphan Education Sponsorship', description: 'Sponsor school fees, uniforms and books for 40 orphans.', goal_amount: 400000, raised: 98000, donors: 41, progress: 25, end_date: null },
    ],
    2: [
      { id: 3, title: "Aziz's Heart Surgery", description: 'Help 4-year-old Aziz undergo life-saving open-heart surgery.', goal_amount: 1200000, raised: 890000, donors: 312, progress: 74, end_date: null },
    ],
    3: [
      { id: 4, title: 'Flood Relief — Tana River', description: 'Emergency food, clean water and shelter for 300 families.', goal_amount: 800000, raised: 120000, donors: 64, progress: 15, end_date: new Date(Date.now() + 86400000 * 30).toISOString().slice(0,10) },
    ],
  };

  function prayerTimes(lat) {
    const fajr = '05:' + (10 + (lat > 0 ? 2 : 0));
    return { Fajr: fajr, Sunrise: '06:30', Dhuhr: '12:30', Asr: '15:45', Maghrib: '18:' + (30 + (lat > 0 ? 2 : 0)), Isha: '19:45', date: new Date().toISOString().slice(0, 10) };
  }
  function nextPrayer() {
    return { name: 'Asr', time: '15:45', remaining_minutes: 90 };
  }

  /* ---------- distance (Near me) helpers — mirror the PHP Haversine ---------- */
  function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const toRad = d => d * Math.PI / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
      + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 2 * R * Math.asin(Math.sqrt(a));
  }
  function withDistance(list, lat, lng, radius) {
    return list
      .filter(it => it.latitude && it.longitude)
      .map(it => ({ ...it, distance_km: haversineKm(lat, lng, it.latitude, it.longitude) }))
      .filter(it => it.distance_km <= radius)
      .sort((a, b) => a.distance_km - b.distance_km);
  }

  /* ---------- route mock requests ---------- */
  function handle(path, opts) {
    const method = (opts && opts.method) || 'GET';
    const url = new URL(path, window.location.href);
    const action = url.searchParams.get('action');

    if (path.startsWith('api/csrf.php')) return { csrf_token: 'mock-csrf-token' };

    if (path.startsWith('api/oauth.php')) {
      if (action === 'login') {
        return { mode: 'demo', message: 'Simulated Google sign-in (demo mode)', login_url: 'api/oauth.php?action=callback&provider=google&code=mock-oauth-code&state=mock-state' };
      }
      if (action === 'callback') {
        if (url.searchParams.get('code') !== 'mock-oauth-code') throw Object.assign(new Error('Could not exchange the authorization code with Google'), { status: 502 });
        mockUser = {
          id: 100, username: 'oauth_user', email: 'oauth.demo@example.com', full_name: 'OAuth Demo User',
          user_type: 'regular', profile_photo: '', total_reviews: 0, total_checkins: 0,
          email_verified: true,
        };
        return { ok: true, user: mockUser, redirect: 'dashboard.html' };
      }
    }

    if (path.startsWith('api/auth.php')) {
      if (action === 'me') return mockUser;
      if (action === 'logout') return { logged_out: true };
      if (action === 'login') {
        const b = parseBody(opts);
        const isAdmin = b.identifier === 'admin@example.com';
        if ((b.identifier === 'demo@example.com' || isAdmin) && (b.password === 'Demo@123' || b.password === 'Admin@123')) {
          mockUser = {
            id: isAdmin ? 1 : 2,
            username: isAdmin ? 'admin' : 'amina',
            email: b.identifier,
            full_name: isAdmin ? 'Site Administrator' : 'Amina Hassan',
            user_type: isAdmin ? 'admin' : 'regular',
            profile_photo: '', total_reviews: 4, total_checkins: 6, helpful_votes: 12, contributor_level: 2,
          };
          return mockUser;
        }
        throw Object.assign(new Error('Invalid credentials'), { status: 401 });
      }
      if (action === 'register') {
        mockUser = { id: 99, username: parseBody(opts).username, email: parseBody(opts).email, full_name: parseBody(opts).full_name || parseBody(opts).username, user_type: 'regular', profile_photo: '', total_reviews: 0, total_checkins: 0 };
        mockUser.email_verified = false;
        mockUser.verification_link = 'verify.html?token=mock-verify-token-1234567890abcdef';
        return mockUser;
      }
      if (action === 'verify') {
        const b = parseBody(opts);
        if (b.token !== 'mock-verify-token-1234567890abcdef') throw Object.assign(new Error('This verification link is invalid or has expired. Request a new one.'), { status: 422 });
        if (mockUser) mockUser.email_verified = true;
        return { ok: true, user_id: mockUser ? mockUser.id : 99 };
      }
      if (action === 'resend_verification') {
        return { ok: true, message: 'Confirmation link sent', verification_link: 'verify.html?token=mock-verify-token-1234567890abcdef' };
      }
      if (action === 'forgot') {
        return { ok: true, message: 'Reset link generated', reset_token: 'mock-reset-token-1234567890abcdef', reset_link: 'reset.html?token=mock-reset-token-1234567890abcdef' };
      }
      if (action === 'reset') {
        const b = parseBody(opts);
        if (b.token !== 'mock-reset-token-1234567890abcdef') throw Object.assign(new Error('This reset link is invalid or has expired'), { status: 422 });
        return { ok: true, message: 'Password reset successfully — you can now login' };
      }
      throw Object.assign(new Error('Unknown action'), { status: 404 });
    }

    if (path.startsWith('api/businesses.php') && action === 'mine') {
      if (!mockUser) throw Object.assign(new Error('Please login to continue'), { status: 401 });
      return [
        { id: 1, name: 'Al-Barakah Restaurant', slug: 'al-barakah-restaurant', city: 'Nairobi', price_range: '$$', is_claimed: 1, is_verified: 1, rating_average: 4.5, review_count: 2, checkin_count: 34, pending_responses: 1 },
      ];
    }

    if (path.startsWith('api/businesses.php')) {
      if (url.searchParams.has('id')) {
        const b = BUSINESS_DETAILS[url.searchParams.get('id')] || BUSINESS_DETAILS[1];
        return { business: b, photos: [{ id: 1, photo_path: b.primary_photo || '', thumbnail_path: '', caption: '', is_primary: 1 }], reviews: REVIEWS, rating_breakdown: { 1: 0, 2: 0, 3: 0, 4: 1, 5: 1 }, similar: BUSINESSES.filter(x => x.id !== b.id).slice(0, 3) };
      }
      if (url.searchParams.has('featured')) return BUSINESSES.filter(b => b.is_verified).slice(0, 6);
      let biz = BUSINESSES;
      if (url.searchParams.has('lat') && url.searchParams.has('lng')) {
        const lat = parseFloat(url.searchParams.get('lat'));
        const lng = parseFloat(url.searchParams.get('lng'));
        const radius = parseFloat(url.searchParams.get('radius') || '50') || 50;
        biz = withDistance(biz, lat, lng, radius);
      }
      return { items: biz, total: biz.length, page: 1, pages: 1, categories: CATEGORIES, cities: CITIES.map(c => ({ city: c, total: 2 })) };
    }

    if (path.startsWith('api/categories.php')) return CATEGORIES;

    if (path.startsWith('api/mosques.php')) {
      if (url.searchParams.has('id')) {
        const m = MOSQUE_DETAILS[url.searchParams.get('id')] || MOSQUE_DETAILS[1];
        return { mosque: m, photos: [{ id: 1, photo_path: m.primary_photo || '', caption: '' }], reviews: m.id === 4 ? [] : REVIEWS.slice(0, 1), prayer: { today: prayerTimes(m.latitude), next: nextPrayer(), week: [1, 2, 3, 4, 5, 6, 7].map(i => ({ date: '', day: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][i - 1], times: prayerTimes(m.latitude) })) } };
      }
      if (url.searchParams.has('top')) return MOSQUES.slice(0, 4).map(m => ({ ...m, next_prayer: nextPrayer() }));
      let mosques = MOSQUES.map(m => ({ ...m, prayer_times: prayerTimes(m.latitude), next_prayer: nextPrayer() }));
      if (url.searchParams.has('lat') && url.searchParams.has('lng')) {
        const lat = parseFloat(url.searchParams.get('lat'));
        const lng = parseFloat(url.searchParams.get('lng'));
        const radius = parseFloat(url.searchParams.get('radius') || '50') || 50;
        mosques = withDistance(mosques, lat, lng, radius);
      }
      return { items: mosques, total: mosques.length, page: 1, pages: 1, cities: CITIES.map(c => ({ city: c, total: 1 })) };
    }

    if (path.startsWith('api/fundis.php')) {
      if (url.searchParams.has('id')) {
        const f = FUNDI_DETAILS[url.searchParams.get('id')] || FUNDI_DETAILS[1];
        return { fundi: f, portfolio: PORTFOLIO[f.id] || [], reviews: f.id === 3 ? REVIEWS.slice(1, 2) : REVIEWS.slice(0, 1) };
      }
      if (url.searchParams.has('top')) return FUNDIS.slice(0, 3);
      let fundis = FUNDIS;
      if (url.searchParams.has('lat') && url.searchParams.has('lng')) {
        const lat = parseFloat(url.searchParams.get('lat'));
        const lng = parseFloat(url.searchParams.get('lng'));
        const radius = parseFloat(url.searchParams.get('radius') || '50') || 50;
        fundis = withDistance(fundis, lat, lng, radius);
      }
      return { items: fundis, total: fundis.length, page: 1, pages: 1, skills: ['Plumbing', 'Tailoring', 'Electrician', 'Carpentry'], cities: CITIES.map(c => ({ city: c, total: 1 })) };
    }

    if (path.startsWith('api/reviews.php')) {
      const b = parseBody(opts);
      if (b.action === 'create') return { review_id: 99 };
      if (b.action === 'react' || b.action === 'helpful') {
        const reactions = mockUser ? ['useful'] : [];
        return { counts: { useful: 13, funny: 8, cool: 3 }, user_reactions: reactions };
      }
      if (b.action === 'delete') return { deleted: true };
    }

    if (path.startsWith('api/checkin.php')) return { checkin_count: 35 };

    if (path.startsWith('api/favorites.php')) {
      if (method === 'GET' && action === 'status') {
        return { saved: mockFavorites.has(url.searchParams.get('favoritable_type') + ':' + url.searchParams.get('favoritable_id')) };
      }
      if (method === 'GET' && action === 'mine') {
        if (!mockUser) throw Object.assign(new Error('Please login to continue'), { status: 401 });
        return Array.from(mockFavorites).map(key => {
          const [type, id] = key.split(':');
          const pool = type === 'business' ? BUSINESSES : type === 'mosque' ? MOSQUES : FUNDIS;
          const it = pool.find(x => String(x.id) === id);
          if (!it) return null;
          return { type, id: Number(id), url: type + '.html?id=' + id, name: it.name || it.full_name, city: it.city, rating_average: it.rating_average, review_count: it.review_count, primary_photo: it.primary_photo || it.profile_photo || null };
        }).filter(Boolean);
      }
      if (method === 'POST') {
        if (!mockUser) throw Object.assign(new Error('Please login to continue'), { status: 401 });
        const b = parseBody(opts);
        const key = b.favoritable_type + ':' + b.favoritable_id;
        let saved;
        if (mockFavorites.has(key)) { mockFavorites.delete(key); saved = false; }
        else { mockFavorites.add(key); saved = true; }
        return { saved };
      }
    }

    if (path.startsWith('api/upload.php')) {
      return { file: 'assets/img/sample/restaurant-1.svg', thumbnail: 'assets/img/sample/restaurant-1.svg', mime: 'image/svg+xml', size: 1000 };
    }


    if (path.startsWith('api/ads.php')) {
      if (method === 'GET') {
        if (action === 'list') {
          if (!mockUser || mockUser.user_type !== 'admin') throw Object.assign(new Error('Admins only'), { status: 403 });
          return ADS.map(a => ({ ...a, placement_name: a.placement_id === 1 ? 'Homepage header' : a.placement_id === 3 ? 'Search results' : a.placement_id === 5 ? 'Detail page' : 'Listing page', placement_location: a.placement_id === 1 ? 'homepage_header' : a.placement_id === 3 ? 'search_results' : a.placement_id === 5 ? 'detail_page' : 'listing_page' }));
        }
        const placement = url.searchParams.get('placement');
        const ids = ADS_BY_PLACEMENT[placement] || [];
        return { ads: ids.map(i => ADS.find(a => a.id === i)).filter(Boolean), placement: { location: placement, max_ads: 2 } };
      }
      const b = parseBody(opts);
      if (action === 'impression') return { impressions: 1241 };
      if (action === 'click') return { clicks: 87 };
      if (action === 'create') {
        if (!mockUser || mockUser.user_type !== 'admin') throw Object.assign(new Error('Admins only'), { status: 403 });
        return { ad_id: 99 };
      }
      if (action === 'toggle' || action === 'delete') {
        if (!mockUser || mockUser.user_type !== 'admin') throw Object.assign(new Error('Admins only'), { status: 403 });
        return { status: b.status || 'paused', deleted: action === 'delete' };
      }
    }

    if (path.startsWith('api/suggest.php')) {
      const q = (url.searchParams.get('q') || '').toLowerCase();
      if (q.length < 2) return [];
      const out = [];
      BUSINESSES.filter(b => b.name.toLowerCase().includes(q)).slice(0, 3).forEach(b =>
        out.push({ type: 'business', label: b.name, sub: 'Business · ' + b.city, url: 'business.html?id=' + b.id }));
      MOSQUES.filter(m => m.name.toLowerCase().includes(q)).slice(0, 3).forEach(m =>
        out.push({ type: 'mosque', label: m.name, sub: 'Mosque · ' + m.city, url: 'mosque.html?id=' + m.id }));
      FUNDIS.filter(f => (f.profession || '').toLowerCase().includes(q) || (f.full_name || '').toLowerCase().includes(q)).slice(0, 3).forEach(f =>
        out.push({ type: 'fundi', label: f.full_name, sub: f.profession + ' · ' + f.city, url: 'fundi.html?id=' + f.id }));
      CATEGORIES.filter(c => c.name.toLowerCase().includes(q)).slice(0, 2).forEach(c =>
        out.push({ type: 'category', label: c.name, sub: 'Category', url: 'businesses.html?category=' + c.slug }));
      return out.slice(0, 10);
    }

    if (path.startsWith('api/activity.php')) {
      return REVIEWS.map(r => ({
        id: r.id, rating: r.rating, title: r.title, content: r.content, created_at: r.created_at,
        reviewable_id: 1, reviewable_type: 'business',
        user_name: r.user.full_name, profile_photo: r.user.profile_photo || '',
        contributor_level: r.user.contributor_level || 1, verification_badge: r.user.verification_badge || 'none',
        listing_name: 'Al-Barakah Restaurant', listing_url: 'business.html?id=1', listing_type: 'business',
      }));
    }

    if (path.startsWith('api/reports.php')) {
      if (action === 'queue') {
        if (!mockUser || mockUser.user_type !== 'admin') throw Object.assign(new Error('Admins only'), { status: 403 });
        return [
          { id: 1, reportable_id: 2, reportable_type: 'review', reason: 'spam', description: 'Looks like a fake review promoting a competitor.', status: 'pending', reporter_name: 'Yusuf Omar', created_at: new Date(Date.now() - 86400000).toISOString() },
          { id: 2, reportable_id: 1, reportable_type: 'business', reason: 'closed', description: 'This restaurant has permanently closed.', status: 'pending', reporter_name: 'Amina Hassan', created_at: new Date(Date.now() - 86400000 * 2).toISOString() },
        ];
      }
      if (!mockUser) throw Object.assign(new Error('Please login to continue'), { status: 401 });
      const b = parseBody(opts);
      if (b.action === 'create') return { reported: true };
      if (b.action === 'admin_resolve') {
        if (mockUser.user_type !== 'admin') throw Object.assign(new Error('Admins only'), { status: 403 });
        return { resolved: true };
      }
      if (action === 'mine') return [];
      throw Object.assign(new Error('Unknown action'), { status: 404 });
    }

    if (path.startsWith('api/businesses.php') && method === 'POST') {
      if (!mockUser) throw Object.assign(new Error('Please login to continue'), { status: 401 });
      const b = parseBody(opts);
      if (b.action === 'claim') return { claimed: true };
      if (b.action === 'respond') return { responded: true };
      if (b.action === 'update') return { updated: true };
    }

    if (path.startsWith('api/charities.php')) {
      if (url.searchParams.has('id')) {
        const c = CHARITIES.find(x => String(x.id) === url.searchParams.get('id')) || CHARITIES[0];
        return { charity: c, raised: c.raised, donors: c.donors, campaigns: CAMPAIGNS[c.id] || [] };
      }
      if (url.searchParams.has('top')) return CHARITIES;
      return { items: CHARITIES, total: CHARITIES.length, page: 1, pages: 1, categories: [...new Set(CHARITIES.map(c => c.category))].map(c => ({ category: c, total: 1 })) };
    }

    if (path.startsWith('api/donations.php')) {
      const b = parseBody(opts);
      if (b.payment_method === 'mpesa') return { donation_id: 99, status: 'completed', amount: b.amount, payment: { provider: 'mpesa', phone: b.donor_phone, simulated: true }, payment_url: null, message: 'Thank you for your donation! 🙏' };
      if (b.payment_method === 'paypal') return { donation_id: 99, status: 'pending', amount: b.amount, payment: { provider: 'paypal' }, payment_url: 'https://www.sandbox.paypal.com/cgi-bin/webscr', message: 'Donation recorded — please complete the payment to finalize it.' };
      return { donation_id: 99, status: 'pending', amount: b.amount, payment: { provider: 'bank' }, payment_url: null, message: 'Donation recorded — please complete the bank transfer to finalize your donation.' };
    }

    if (path.startsWith('api/quotes.php')) {
      const b = parseBody(opts);
      return { quote_id: 7, whatsapp_link: 'https://wa.me/254712888999?text=' + encodeURIComponent("Salaam! I'm " + (b.name || '') + ". I'd like a quote for:\n\n" + (b.description || '')), fundi_name: 'Abdullahi Said', fundi_wa: '254712888999', message: 'Request saved — send it to Abdullahi Said on WhatsApp' };
    }

    if (path.startsWith('api/notifications.php')) {
      if (!mockUser) throw Object.assign(new Error('Please login to continue'), { status: 401 });
      if (method === 'GET' && url.searchParams.has('unread')) return { unread_count: mockUser && mockNotifRead < mockNotifs.length ? mockNotifs.length - mockNotifRead : 0 };
      if (method === 'GET') return { items: mockNotifs.map(n => ({ ...n, is_read: n.id <= mockNotifRead ? 1 : 0 })), unread_count: mockNotifs.length - mockNotifRead };
      const b = parseBody(opts);
      if (b.action === 'read_all') { mockNotifRead = mockNotifs.length; return { read_all: true }; }
      if (b.action === 'read') { mockNotifRead = Math.max(mockNotifRead, Number(b.id)); return { read: true }; }
    }

    throw Object.assign(new Error('Not found in mock: ' + path), { status: 404 });
  }

  function parseBody(opts) {
    if (!opts || !opts.body) return {};
    if (typeof opts.body === 'string') {
      try { return JSON.parse(opts.body); } catch (e) { /* fallthrough */ }
      return Object.fromEntries(new URLSearchParams(opts.body));
    }
    return opts.body;
  }

  let mockUser = null;
  let mockNotifRead = 0;
  const mockNotifs = [
    { id: 3, type: 'new_review', title: 'New ★★★★★ review', message: 'Yusuf Omar reviewed Al-Barakah Restaurant: "The chicken biryani is incredible…"', link: 'business.html?id=1', is_read: 0, created_at: new Date(Date.now() - 3600000 * 2).toISOString() },
    { id: 2, type: 'donation', title: 'New donation: KSh 5,000', message: 'You received a donation for Nuru Medical Fund.', link: 'dashboard.html', is_read: 0, created_at: new Date(Date.now() - 86400000).toISOString() },
    { id: 1, type: 'system', title: 'Welcome to Ummah Directory!', message: 'Your account is ready.', link: 'profile.html', is_read: 1, created_at: new Date(Date.now() - 86400000 * 3).toISOString() },
  ];
  const mockFavorites = new Set(['business:1', 'mosque:3']); // demo user's saved items

  // wrap: return {success:true,data:...} shape or throw with status
  return async function mockApi(path, opts) {
    try {
      const data = await Promise.resolve(handle(path, opts));
      return data;
    } catch (e) {
      throw e;
    }
  };
})();
