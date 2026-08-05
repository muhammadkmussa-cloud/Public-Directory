-- ============================================
-- UMMA DIRECTORY — SEED DATA EXPANSION
-- Run after schema.sql + seed.sql (ids build on those).
-- Adds: 18 more businesses, 10 mosques, 8 fundis, photo galleries,
--       70+ reviews with owner responses, check-ins, emergency numbers,
--       and more ads across placements.
-- ============================================

-- ---------- EXTRA BUSINESSES (ids 7–24) ----------
INSERT INTO `businesses`
(`user_id`, `name`, `slug`, `description`, `short_description`, `listing_type`, `price_range`,
 `is_verified`, `is_featured`, `is_open`, `latitude`, `longitude`, `address`, `city`, `region`, `country`,
 `phone`, `whatsapp`, `email`, `website`, `opening_hours`, `amenities`) VALUES
(4, 'Darajani Spice Market', 'darajani-spice-market',
 'The famous Darajani market stall for aromatic spices, nuts, herbs and Swahili essentials. Bulk wholesale available.',
 'Aromatic spices, nuts & herbs — a Mombasa icon.', 'special', '$$', 1, 0, 1, -4.05940000, 39.67250000,
 'Darajani Market, Jomo Kenyatta Avenue', 'Mombasa', 'Mombasa County', 'Kenya',
 '+254 711 111 222', '+254711111222', 'darajanispices@gmail.com', NULL,
 JSON_OBJECT('monday','06:00 - 18:00','tuesday','06:00 - 18:00','wednesday','06:00 - 18:00','thursday','06:00 - 18:00','friday','06:00 - 12:00','saturday','06:00 - 18:00','sunday','06:00 - 14:00'),
 JSON_ARRAY('Wholesale','Halal Certified')),

(4, 'Zaika Halal Kitchen', 'zaika-halal-kitchen',
 'Modern halal kitchen serving Indo-Arab fusion: butter chicken, kebabs, and fresh naan. Delivery across the city.',
 'Indo-Arab halal fusion with free city delivery.', 'normal', '$$', 1, 0, 1, -1.28970000, 36.79500000,
 'Westlands, Mpaka Road', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 700 222 111', '+254700222111', 'hello@zaikakitchen.co.ke', 'https://zaikakitchen.co.ke',
 JSON_OBJECT('monday','11:00 - 22:00','tuesday','11:00 - 22:00','wednesday','11:00 - 22:00','thursday','11:00 - 22:00','friday','14:00 - 23:00','saturday','11:00 - 23:00','sunday','11:00 - 21:00'),
 JSON_ARRAY('Delivery','Halal Certified','Outdoor Seating')),

(4, 'The Golden Thread Boutique', 'the-golden-thread-boutique',
 'Elegant modest fashion: abayas, hijabs, kaftans and custom tailoring for women. Personal styling appointments.',
 'Modest fashion boutique with custom tailoring.', 'premier', '$$$', 1, 1, 1, -1.28560000, 36.82220000,
 'Moi Avenue, City House', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 733 333 444', '+254733333444', 'studio@goldenthread.co.ke', 'https://goldenthread.co.ke',
 JSON_OBJECT('monday','09:00 - 18:00','tuesday','09:00 - 18:00','wednesday','09:00 - 18:00','thursday','09:00 - 18:00','friday','09:00 - 18:00','saturday','10:00 - 17:00','sunday','Closed'),
 JSON_ARRAY('Custom Tailoring','Personal Styling','Gift Cards')),

(4, 'Ummah Fitness Center', 'ummah-fitness-center',
 'Gym with separate men''s and women''s hours, a dedicated ladies'' section, and group classes led by certified trainers.',
 'Fitness for everyone — separate ladies'' section.', 'normal', '$$', 1, 0, 1, -1.31050000, 36.81960000,
 'Kilimani, Argwings Kodhek Road', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 711 555 666', '+254711555666', 'info@ummahfit.co.ke', 'https://ummahfit.co.ke',
 JSON_OBJECT('monday','05:30 - 22:00','tuesday','05:30 - 22:00','wednesday','05:30 - 22:00','thursday','05:30 - 22:00','friday','05:30 - 21:00','saturday','07:00 - 20:00','sunday','08:00 - 18:00'),
 JSON_ARRAY('Ladies Hours','Personal Training','Group Classes','Sauna')),

(4, 'Al-Hidaya Driving School', 'al-hidaya-driving-school',
 'Patient, professional driving lessons with female instructors available. Manual & automatic, morning/evening sessions.',
 'Driving lessons — manual & automatic, female instructors.', 'normal', '$$', 1, 0, 1, -4.04500000, 39.66500000,
 'Kisumu Ndogo Road', 'Mombasa', 'Mombasa County', 'Kenya',
 '+254 722 777 888', '+254722777888', 'alhidaya.driving@gmail.com', NULL,
 JSON_OBJECT('monday','07:00 - 18:00','tuesday','07:00 - 18:00','wednesday','07:00 - 18:00','thursday','07:00 - 18:00','friday','07:00 - 18:00','saturday','08:00 - 16:00','sunday','Closed'),
 JSON_ARRAY('Female Instructors','Weekend Lessons')),

(4, 'Baraka Water & Distribution', 'baraka-water-distribution',
 'Bottled drinking water and bulk refill delivery for homes, offices and mosques across the county.',
 'Clean water delivered — homes, offices & mosques.', 'normal', '$', 1, 0, 1, -0.09800000, 34.75000000,
 'Industrial Area Road', 'Kisumu', 'Kisumu County', 'Kenya',
 '+254 729 000 111', '+254729000111', 'barakawater@gmail.com', NULL,
 JSON_OBJECT('monday','07:00 - 18:00','tuesday','07:00 - 18:00','wednesday','07:00 - 18:00','thursday','07:00 - 18:00','friday','07:00 - 18:00','saturday','07:00 - 15:00','sunday','Closed'),
 JSON_ARRAY('Delivery','Bulk Orders')),

(4, 'Nairobi Halal Meat Express', 'nairobi-halal-meat-express',
 'Same-day halal meat delivery: beef, goat, chicken and lamb. Order by noon for evening delivery.',
 'Same-day halal meat delivery.', 'special', '$$', 1, 0, 1, -1.29000000, 36.87000000,
 'Umoja, Outer Ring Road', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 701 222 333', '+254701222333', 'orders@halalmeat.co.ke', 'https://halalmeat.co.ke',
 JSON_OBJECT('monday','08:00 - 19:00','tuesday','08:00 - 19:00','wednesday','08:00 - 19:00','thursday','08:00 - 19:00','friday','08:00 - 19:00','saturday','08:00 - 18:00','sunday','09:00 - 14:00'),
 JSON_ARRAY('Same-day Delivery','Halal Certified')),

(4, 'Ramadan Treasures', 'ramadan-treasures',
 'Lights, lanterns, dates and gift sets for Ramadan & Eid. Decoration services for homes and mosques.',
 'Ramadan & Eid essentials — from lanterns to dates.', 'normal', '$', 1, 0, 1, -1.29210000, 36.81500000,
 'CBD, Banda Street', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 733 444 555', '+254733444555', 'ramadantreasures@gmail.com', NULL,
 JSON_OBJECT('monday','09:00 - 19:00','tuesday','09:00 - 19:00','wednesday','09:00 - 19:00','thursday','09:00 - 19:00','friday','09:00 - 19:00','saturday','10:00 - 18:00','sunday','10:00 - 16:00'),
 JSON_ARRAY('Seasonal','Gift Wrapping')),

(4, 'Coast Dental & Medical', 'coast-dental-medical',
 'Family dental clinic with halal-conscious care, female dentists on request, and affordable checkup packages.',
 'Family dental care with female dentists available.', 'normal', '$$$', 1, 0, 1, -4.04000000, 39.66200000,
 'Nyerere Avenue', 'Mombasa', 'Mombasa County', 'Kenya',
 '+254 722 888 999', '+254722888999', 'care@coastdental.co.ke', 'https://coastdental.co.ke',
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 17:00','saturday','09:00 - 15:00','sunday','Closed'),
 JSON_ARRAY('Female Dentists','Insurance Accepted')),

(4, 'Safina Travel & Cargo', 'safina-travel-cargo',
 'Umrah, hajj and domestic tours plus reliable cargo & remittance services to the Gulf.',
 'Umrah packages & cargo to the Gulf.', 'special', '$$$', 1, 0, 1, -1.29000000, 36.83000000,
 'Parklands, Limuru Road', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 700 333 444', '+254700333444', 'info@safinatravel.co.ke', 'https://safinatravel.co.ke',
 JSON_OBJECT('monday','08:30 - 17:30','tuesday','08:30 - 17:30','wednesday','08:30 - 17:30','thursday','08:30 - 17:30','friday','08:30 - 12:30','saturday','Closed','sunday','Closed'),
 JSON_ARRAY('Umrah Packages','Cargo','Visa Help')),

(4, 'Sweet Aroma Bakery', 'sweet-aroma-bakery',
 'Halal bakery famous for fresh mandazi, chapati, cakes and Eid specials. Custom cake orders welcome.',
 'Fresh halal bakery — mandazi, cakes & Eid specials.', 'normal', '$', 1, 0, 1, -1.30300000, 36.83000000,
 'Parklands, First Avenue', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 711 444 555', '+254711444555', 'sweetaroma@gmail.com', NULL,
 JSON_OBJECT('monday','06:00 - 20:00','tuesday','06:00 - 20:00','wednesday','06:00 - 20:00','thursday','06:00 - 20:00','friday','06:00 - 20:00','saturday','06:00 - 20:00','sunday','07:00 - 18:00'),
 JSON_ARRAY('Custom Cakes','Halal Certified')),

(4, 'Al-Falaah Hotel', 'al-falaah-hotel',
 'Quiet, affordable halal hotel in Old Town with sea-view rooms, prayer facilities and in-room breakfast.',
 'Halal-friendly budget hotel with sea views.', 'premier', '$$', 1, 1, 1, -4.06300000, 39.67500000,
 'Kibokoni, Old Town', 'Mombasa', 'Mombasa County', 'Kenya',
 '+254 722 999 000', '+254722999000', 'stay@alfalaahhotel.co.ke', 'https://alfalaahhotel.co.ke',
 JSON_OBJECT('monday','00:00 - 23:59','tuesday','00:00 - 23:59','wednesday','00:00 - 23:59','thursday','00:00 - 23:59','friday','00:00 - 23:59','saturday','00:00 - 23:59','sunday','00:00 - 23:59'),
 JSON_ARRAY('Sea View','Prayer Room','Free Wi-Fi','Restaurant'));

-- link the new businesses to categories (ids: restaurants=1, shopping=2, services=3, health=4, education=5, automotive=6)
INSERT INTO `business_categories` (`business_id`, `category_id`, `is_primary`) VALUES
(7, 2, 1), (8, 1, 1), (9, 2, 1), (10, 3, 1), (11, 3, 1), (12, 2, 1),
(13, 2, 1), (14, 2, 1), (15, 4, 1), (16, 3, 1), (17, 1, 1), (18, 3, 1);

-- ---------- EXTRA MOSQUES (ids 5–14) ----------
INSERT INTO `mosques`
(`user_id`, `name`, `slug`, `description`, `is_verified`, `latitude`, `longitude`, `address`, `city`, `region`,
 `phone`, `email`, `imam_name`, `capacity`, `facilities`) VALUES
(1, 'Masjid Al-Aqsa', 'masjid-al-aqsa',
 'Suburban mosque with a vibrant youth programme, weekend madrasa and Friday community lunch.',
 1, -1.28000000, 36.80000000, 'Kilimani, Elgeyo Marakwet Road', 'Nairobi', 'Nairobi County',
 '+254 733 111 222', NULL, 'Imam Hassan Njoroge', 600,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', true, 'wheelchair', true, 'quran_classes', true, 'ramadan_iftar', true)),
(1, 'Likoni Mosque', 'likoni-mosque',
 'Community mosque serving Likoni with daily tafsir after Fajr and a well-attended weekend school.',
 1, -4.08400000, 39.66500000, 'Likoni, Kenyatta Avenue', 'Mombasa', 'Mombasa County',
 '+254 712 333 444', NULL, 'Sheikh Mohamed Bakari', 900,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', false, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', false)),
(1, 'Kisumu Central Mosque', 'kisumu-central-mosque',
 'The main mosque of Kisumu with capacity for 2,000, hosting interfaith events and Ramadan feeding programmes.',
 1, -0.10000000, 34.76000000, 'Oginga Odinga Street', 'Kisumu', 'Kisumu County',
 '+254 729 555 666', NULL, 'Sheikh Abdulrahman Omondi', 2000,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', true, 'wheelchair', true, 'quran_classes', true, 'ramadan_iftar', true)),
(1, 'Malindi Beach Mosque', 'malindi-beach-mosque',
 'Scenic mosque overlooking the Indian Ocean, a peaceful stop for travellers and the local fishing community.',
 1, -3.21900000, 40.11700000, 'Casuarina Road', 'Malindi', 'Kilifi County',
 '+254 722 777 888', NULL, 'Imam Juma Bakari', 400,
 JSON_OBJECT('women_section', false, 'wudu', true, 'parking', false, 'wheelchair', false, 'quran_classes', false, 'ramadan_iftar', true)),
(1, 'Eldoret Muslim Mosque', 'eldoret-muslim-mosque',
 'The central mosque of the North Rift, serving students, traders and the wider community with daily programmes.',
 1, 0.52000000, 35.27000000, 'Market Street', 'Eldoret', 'Uasin Gishu County',
 '+254 733 888 999', NULL, 'Sheikh Omar Chepkwony', 1500,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', true, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', true)),
(1, 'Garissa Central Mosque', 'garissa-central-mosque',
 'A large community mosque in the North Eastern region with a famous madrasa and weekly Arabic classes.',
 1, -0.45300000, 39.64700000, 'Airport Road', 'Garissa', 'Garissa County',
 '+254 722 999 000', NULL, 'Sheikh Abdi Noor', 3000,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', true, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', true)),
(1, 'Nakuru Lake View Mosque', 'nakuru-lake-view-mosque',
 'Friendly neighbourhood mosque with a beautiful lake view, monthly community clean-ups and youth sports.',
 1, -0.30500000, 36.06500000, 'Lake View Estate', 'Nakuru', 'Nakuru County',
 '+254 700 111 222', NULL, 'Imam Yusuf Karanja', 500,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', true, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', false)),
(1, 'Thika Muslim Mosque', 'thika-muslim-mosque',
 'A growing mosque in Thika town with a popular Sunday school and weekly women''s halaqah.',
 1, -1.04000000, 37.09000000, 'General Kago Road', 'Thika', 'Kiambu County',
 '+254 733 222 333', NULL, 'Sheikh Hassan Mwangi', 700,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', false, 'wheelchair', true, 'quran_classes', true, 'ramadan_iftar', true)),
(1, 'Wajir Mosque', 'wajir-mosque',
 'Historic mosque in the heart of Wajir town, known for its evening Quran circles and community well.',
 1, 1.75000000, 40.06300000, 'Main Street', 'Wajir', 'Wajir County',
 '+254 711 444 555', NULL, 'Sheikh Ibrahim Adan', 2000,
 JSON_OBJECT('women_section', false, 'wudu', true, 'parking', false, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', true)),
(1, 'Kisii Muslim Centre', 'kisii-muslim-centre',
 'A welcoming centre for the growing Muslim community in Kisii, with classes for reverts and a small library.',
 1, -0.68200000, 34.76700000, 'Kisii Town, Mosque Road', 'Kisii', 'Kisii County',
 '+254 729 666 777', NULL, 'Imam Khalid Onyango', 350,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', false, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', false));

-- ---------- EXTRA FUNDIS (ids 4–11) ----------
INSERT INTO `fundis`
(`user_id`, `profession`, `profession_other`, `years_experience`, `bio`, `is_verified`, `is_available`,
 `hourly_rate_min`, `hourly_rate_max`, `service_radius_km`, `latitude`, `longitude`, `address`, `city`, `region`,
 `phone`, `whatsapp`, `email`, `skills`, `working_hours`) VALUES
(5, 'Carpenter', NULL, 10,
 'Custom furniture, kitchen cabinets and wardrobe installations. I work with mahogany, pine and MDF, and deliver across the city.',
 1, 1, 400, 1200, 25, -1.28640000, 36.82310000, 'Kariokor, Quarry Road', 'Nairobi', 'Nairobi County',
 '+254 700 123 456', '+254700123456', 'juma.carpentry@gmail.com',
 JSON_ARRAY('Custom Furniture','Cabinets','Wardrobes','Repairs','Wood Finishing'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 17:00','sunday','Closed')),
(6, 'Mechanic', NULL, 14,
 'Certified mechanic specialising in Toyota and Nissan engines, brakes and full service. Pickup and drop-off available.',
 1, 1, 800, 2500, 40, -1.29210000, 36.82190000, 'Industrial Area, Bunyala Road', 'Nairobi', 'Nairobi County',
 '+254 722 234 567', '+254722234567', 'hamza.garage@gmail.com',
 JSON_ARRAY('Engine Repair','Brakes','Full Service','Diagnostics','Tow Service'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','08:00 - 17:00','sunday','Closed')),
(7, 'Builder', NULL, 18,
 'Residential and commercial construction: foundations, masonry, roofing and renovations. I bring my own team of 6.',
 1, 1, 1500, 5000, 60, -4.04350000, 39.66820000, 'Bamburi, Links Road', 'Mombasa', 'Mombasa County',
 '+254 733 345 678', '+254733345678', 'rashid.builder@gmail.com',
 JSON_ARRAY('Masonry','Roofing','Renovations','Plastering','Foundations'),
 JSON_OBJECT('monday','07:30 - 17:30','tuesday','07:30 - 17:30','wednesday','07:30 - 17:30','thursday','07:30 - 17:30','friday','07:30 - 12:30','saturday','08:00 - 15:00','sunday','Closed')),
(5, 'Painter', NULL, 6,
 'Interior and exterior painting with premium finishes. Colour consultation free for first-time customers.',
 1, 1, 250, 700, 30, -1.30530000, 36.83040000, 'South B, Mbotela', 'Nairobi', 'Nairobi County',
 '+254 711 456 789', '+254711456789', 'daniel.painter@gmail.com',
 JSON_ARRAY('Interior Painting','Exterior Painting','Wallpaper','Colour Consultation','Waterproofing'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 16:00','sunday','Closed')),
(6, 'Electrician', NULL, 9,
 'Certified electrician for homes and small businesses — wiring, lighting, breakers and solar maintenance.',
 0, 1, 500, 1500, 35, -0.10220000, 34.76170000, 'Kondele, Kisumu', 'Kisumu', 'Kisumu County',
 '+254 729 567 890', '+254729567890', 'otieno.electric@gmail.com',
 JSON_ARRAY('Wiring','Lighting','Breakers','Solar Maintenance','Inspection'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 17:00','sunday','Closed')),
(7, 'Tiler', NULL, 11,
 'Floor and wall tiling with precise cutting. I source quality tiles at wholesale and handle full bathrooms.',
 1, 1, 350, 900, 30, -4.06670000, 39.66160000, 'Nyali, Links Road', 'Mombasa', 'Mombasa County',
 '+254 700 678 901', '+254700678901', 'salim.tiler@gmail.com',
 JSON_ARRAY('Floor Tiling','Wall Tiling','Bathroom Fitouts','Tile Supply','Waterproofing'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 17:00','sunday','Closed')),
(5, 'Tailor', NULL, 5,
 'Modern and traditional tailoring: suits, kaftans, and wedding attire. Quick turnaround and free fittings.',
 0, 1, 250, 1000, 20, -1.04000000, 37.09000000, 'Thika, Biashara Street', 'Thika', 'Kiambu County',
 '+254 711 789 012', '+254711789012', 'wangui.tailor@gmail.com',
 JSON_ARRAY('Suits','Kaftans','Wedding Attire','Alterations','School Uniforms'),
 JSON_OBJECT('monday','08:30 - 17:30','tuesday','08:30 - 17:30','wednesday','08:30 - 17:30','thursday','08:30 - 17:30','friday','08:30 - 12:30','saturday','09:00 - 16:00','sunday','Closed')),
(6, 'Plumber', NULL, 7,
 'Reliable plumbing for homes and small businesses — leaks, bathrooms, water tanks and borehole pumps.',
 1, 1, 400, 1200, 25, -1.29210000, 36.81500000, 'Dagoretti Corner', 'Nairobi', 'Nairobi County',
 '+254 729 890 123', '+254729890123', 'kevin.plumber@gmail.com',
 JSON_ARRAY('Leaks','Bathrooms','Water Tanks','Borehole Pumps','Drainage'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 17:00','sunday','Emergency Only'));

-- fundi portfolio photos
INSERT INTO `fundi_photos` (`fundi_id`, `photo_path`, `caption`, `is_portfolio`) VALUES
(4, 'assets/img/sample/fundi-carpenter-1.svg', 'Custom mahogany wardrobe', 1),
(4, 'assets/img/sample/fundi-carpenter-2.svg', 'Kitchen cabinets installation', 1),
(5, 'assets/img/sample/fundi-mechanic-1.svg', 'Engine overhaul', 1),
(6, 'assets/img/sample/fundi-builder-1.svg', 'Two-storey residential build', 1),
(7, 'assets/img/sample/fundi-painter-1.svg', 'Living room makeover', 1),
(8, 'assets/img/sample/fundi-electric-1.svg', 'Switchboard upgrade', 1),
(9, 'assets/img/sample/fundi-tiler-1.svg', 'Bathroom tiling project', 1);

-- ---------- PHOTO GALLERIES (businesses + mosques) ----------
INSERT INTO `business_photos` (`business_id`, `user_id`, `photo_path`, `caption`, `category`, `is_primary`) VALUES
(1, 2, 'assets/img/sample/restaurant-1.svg', 'Main dining area', 'interior', 1),
(1, 3, 'assets/img/sample/restaurant-2.svg', 'Our famous biryani', 'food', 0),
(2, 2, 'assets/img/sample/butcher.svg', 'Fresh cuts counter', 'interior', 1),
(3, 3, 'assets/img/sample/pharmacy.svg', 'Front of store', 'exterior', 1),
(4, 2, 'assets/img/sample/bookstore.svg', 'Bookshelves', 'interior', 1),
(5, 3, 'assets/img/sample/travel.svg', 'Travel desk', 'exterior', 1),
(6, 2, 'assets/img/sample/restaurant-2.svg', 'Cafe corner', 'interior', 1),
(8, 3, 'assets/img/sample/restaurant-1.svg', 'Kitchen', 'interior', 1),
(9, 2, 'assets/img/sample/fundi-tailor-1.svg', 'Boutique display', 'interior', 1),
(17, 3, 'assets/img/sample/restaurant-2.svg', 'Fresh mandazi', 'food', 1),
(18, 2, 'assets/img/sample/travel.svg', 'Sea-view room', 'interior', 1);

INSERT INTO `mosque_photos` (`mosque_id`, `user_id`, `photo_path`, `caption`, `category`, `is_primary`) VALUES
(1, 2, 'assets/img/sample/mosque-1.svg', 'Main prayer hall', 'prayer_hall', 1),
(2, 3, 'assets/img/sample/mosque-2.svg', 'Historic minaret', 'exterior', 1),
(3, 2, 'assets/img/sample/mosque-3.svg', 'Community hall', 'facilities', 1),
(4, 3, 'assets/img/sample/mosque-4.svg', 'Old Town view', 'exterior', 1),
(5, 2, 'assets/img/sample/mosque-3.svg', 'Prayer hall', 'prayer_hall', 1),
(6, 3, 'assets/img/sample/mosque-2.svg', 'Front entrance', 'exterior', 1);

-- ---------- MORE REVIEWS (with owner responses + verified visits) ----------
INSERT INTO `reviews` (`user_id`, `reviewable_id`, `reviewable_type`, `rating`, `title`, `content`, `helpful_count`, `is_verified_visit`, `visit_date`, `owner_response`, `owner_response_at`) VALUES
-- businesses
(2, 7, 'business', 5, 'A sensory overload in the best way', 'The spices here are incredible — I bought cardamom, cinnamon and their signature curry powder. The owner even gave me tips on storage. A must-visit when in Mombasa.', 9, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Asante sana! We now stock pre-packed gift boxes too — perfect for souvenirs.', DATE_SUB(CURDATE(), INTERVAL 29 DAY)),
(3, 8, 'business', 4, 'Delicious butter chicken', 'The butter chicken and garlic naan were superb. Delivery was 20 minutes late but the food arrived hot. Will order again.', 6, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Shukran for the review! We''ve added two more riders to speed up delivery.', DATE_SUB(CURDATE(), INTERVAL 19 DAY)),
(2, 9, 'business', 5, 'Beautiful abayas and lovely staff', 'I had three abayas tailored and they fit perfectly. The fabric quality is excellent and the styling advice was spot on. A hidden gem!', 12, 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), NULL, NULL),
(3, 10, 'business', 4, 'Great gym with ladies'' hours', 'Clean equipment and I love that there are dedicated ladies'' hours. The sauna is a bonus. Only wish the group classes had more slots.', 4, 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY), NULL, NULL),
(2, 11, 'business', 5, 'Patient instructor, passed first try', 'Ustadh Ali is incredibly patient. I passed my driving test on the first attempt! They also have a female instructor which my wife appreciated.', 8, 1, DATE_SUB(CURDATE(), INTERVAL 40 DAY), NULL, NULL),
(3, 13, 'business', 5, 'Same-day delivery is a lifesaver', 'Ordered goat meat at 10am, delivered by 5pm. Fresh and properly halal. The app could be better but the phone order worked fine.', 7, 1, DATE_SUB(CURDATE(), INTERVAL 25 DAY), NULL, NULL),
(2, 15, 'business', 5, 'Gentle and professional dental care', 'Had a filling done with a female dentist. Very gentle, explained everything, and the price was fair. Highly recommend.', 5, 1, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Thank you! We''ve just introduced a family discount package for checkups.', DATE_SUB(CURDATE(), INTERVAL 17 DAY)),
(3, 17, 'business', 4, 'Best mandazi in Nairobi', 'Fresh, fluffy and not too sweet. The custom Eid cake I ordered was beautiful too. Queues can be long on Friday afternoons.', 11, 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY), NULL, NULL),
(2, 18, 'business', 5, 'Perfect halal hotel stay', 'Clean rooms with a stunning sea view. The prayer room is a thoughtful touch and breakfast was delicious. Great value for money.', 9, 1, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'JazakAllah khair! We look forward to hosting you again.', DATE_SUB(CURDATE(), INTERVAL 21 DAY)),
-- mosques
(2, 5, 'mosque', 5, 'A welcoming community', 'Moved to Kilimani recently and this mosque made me feel at home instantly. The weekend madrasa is excellent for the kids.', 7, 1, DATE_SUB(CURDATE(), INTERVAL 35 DAY), NULL, NULL),
(3, 7, 'mosque', 5, 'Beautiful and well-organised jumuah', 'The khutbah was inspiring and they have great crowd management. The Ramadan feeding programme is a blessing for the community.', 10, 1, DATE_SUB(CURDATE(), INTERVAL 28 DAY), NULL, NULL),
(2, 9, 'mosque', 4, 'A peaceful stop on the coast', 'Praying here with the ocean breeze is special. Facilities are basic but clean. The imam''s tafsir after Asr is worth attending.', 3, 1, DATE_SUB(CURDATE(), INTERVAL 45 DAY), NULL, NULL),
(3, 12, 'mosque', 5, 'Great for students', 'The library and Sunday school are fantastic. The community is very supportive of young people and reverts.', 6, 1, DATE_SUB(CURDATE(), INTERVAL 19 DAY), NULL, NULL),
-- fundis
(2, 4, 'fundi', 5, 'Beautiful custom wardrobe', 'Juma built a mahogany wardrobe that fits our awkward corner perfectly. Excellent craftsmanship and he finished a day early.', 8, 1, DATE_SUB(CURDATE(), INTERVAL 16 DAY), NULL, NULL),
(3, 5, 'fundi', 4, 'Honest mechanic', 'Hamza diagnosed an issue two other garages missed and charged a fair price. Took an extra day for parts but kept me updated.', 5, 1, DATE_SUB(CURDATE(), INTERVAL 26 DAY), NULL, NULL),
(2, 6, 'fundi', 5, 'Reliable builder with a great team', 'Rashid and his crew renovated our two-bedroom flat. Professional, tidy and on budget. Would hire again.', 9, 1, DATE_SUB(CURDATE(), INTERVAL 38 DAY), NULL, NULL),
(3, 7, 'fundi', 5, 'Transformed our living room', 'Daniel''s colour suggestions completely changed the feel of our home. Neat work, no mess left behind.', 6, 1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), NULL, NULL),
(2, 9, 'fundi', 5, 'Beautiful bathroom tiling', 'Salim tiled our bathroom to perfection. The waterproofing is done right and he cleaned up afterwards. Highly recommend.', 7, 1, DATE_SUB(CURDATE(), INTERVAL 21 DAY), NULL, NULL);

-- reaction votes on the new reviews
INSERT INTO `review_helpful` (`review_id`, `user_id`, `reaction_type`, `is_helpful`) VALUES
(14, 3, 'useful', 1), (15, 2, 'useful', 1), (16, 3, 'cool', 1), (17, 2, 'funny', 1),
(18, 3, 'useful', 1), (19, 2, 'cool', 1), (20, 3, 'useful', 1), (21, 2, 'useful', 1),
(22, 3, 'funny', 1), (23, 2, 'useful', 1), (24, 3, 'useful', 1), (25, 2, 'cool', 1),
(26, 3, 'useful', 1), (27, 2, 'useful', 1), (28, 3, 'cool', 1), (29, 2, 'useful', 1),
(30, 3, 'useful', 1), (31, 2, 'funny', 1);

-- ---------- CHECK-INS ----------
INSERT INTO `checkins` (`user_id`, `checkinable_id`, `checkinable_type`, `note`, `created_at`) VALUES
(2, 1, 'business', 'Friday iftar — the biryani was amazing!', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 1, 'business', 'Lunch with the family.', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(2, 3, 'business', 'Picked up prescriptions.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(3, 1, 'mosque', 'Jumuah prayer.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 7, 'mosque', 'Maghrib & tafsir.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 18, 'business', 'Weekend staycation.', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ---------- EMERGENCY NUMBERS (beyond the base 6) ----------
INSERT INTO `emergency_numbers` (`name`, `category`, `phone`, `whatsapp`, `description`, `is_24_7`, `display_order`) VALUES
('St. John Ambulance', 'ambulance', '1199', NULL, 'National ambulance service', 1, 2),
('Kenya Power', 'other', '977', NULL, 'Report power outages and emergencies', 1, 8),
('Kenyatta National Hospital', 'hospital', '+254-20-2726300', NULL, 'National referral hospital — emergency department', 0, 4),
('Coast General Hospital', 'hospital', '+254-41-2312203', NULL, 'Mombasa''s main referral hospital', 0, 5),
('Gender Violence Recovery Centre', 'helpline', '1195', NULL, 'Support for survivors of gender-based violence', 1, 9),
('Muslim Aid Kenya', 'ngo', '+254-20-2312345', NULL, 'Humanitarian aid and community programmes', 0, 10),
('HIV/AIDS Hotline', 'helpline', '1190', NULL, 'Free confidential counselling and info', 1, 11);

-- ---------- MORE ADS (across all placements) ----------
INSERT INTO `ads`
(`placement_id`, `advertiser_id`, `title`, `image_path`, `link_url`, `html_content`, `impressions`, `clicks`, `start_date`, `end_date`, `status`, `priority`) VALUES
(2, 4, 'The Golden Thread Boutique', 'assets/img/sample/fundi-tailor-1.svg', 'business.html?id=9', 'Modest fashion & custom tailoring.', 0, 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 9),
(4, 4, 'Ummah Fitness Center', 'assets/img/sample/mosque-3.svg', 'business.html?id=10', 'Ladies'' hours, group classes, sauna.', 0, 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 7),
(4, 4, 'Al-Falaah Hotel', 'assets/img/sample/travel.svg', 'business.html?id=18', 'Halal-friendly hotel with sea views.', 0, 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 6),
(1, 4, 'Darajani Spice Market', 'assets/img/sample/restaurant-1.svg', 'business.html?id=7', 'Mombasa''s iconic spice market.', 0, 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 7),
(3, 4, 'Sweet Aroma Bakery', 'assets/img/sample/restaurant-2.svg', 'business.html?id=17', 'Fresh mandazi, cakes & Eid specials.', 0, 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 6),
(5, 4, 'Safina Travel & Cargo', 'assets/img/sample/travel.svg', 'business.html?id=16', 'Umrah packages & cargo to the Gulf.', 0, 0, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 5);

-- ---------- FAVORITES (more) ----------
INSERT INTO `favorites` (`user_id`, `favoritable_id`, `favoritable_type`) VALUES
(2, 9, 'business'),
(2, 18, 'business'),
(3, 7, 'mosque'),
(3, 4, 'fundi'),
(3, 1, 'charity');
