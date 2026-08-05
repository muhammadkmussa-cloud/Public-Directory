-- ============================================
-- UMMA DIRECTORY — SAMPLE DATA
-- Run via `php database/install.php` (recommended)
-- or import manually AFTER the schema and demo users exist.
-- Demo users (ids 1-7) are created by install.php.
-- ============================================

-- ---------- BUSINESSES ----------
INSERT INTO `businesses`
(`user_id`, `name`, `slug`, `description`, `short_description`, `listing_type`, `price_range`,
 `is_verified`, `is_featured`, `is_open`, `latitude`, `longitude`, `address`, `city`, `region`, `country`,
 `phone`, `whatsapp`, `email`, `website`, `opening_hours`, `amenities`) VALUES
(4, 'Al-Barakah Restaurant', 'al-barakah-restaurant',
 'Family-friendly halal restaurant serving authentic Swahili and Arabic cuisine. Famous for our biryani, samosas, and fresh mandazi. Separate family seating available.',
 'Authentic halal Swahili & Arabic cuisine in the heart of the city.',
 'premier', '$$', 1, 1, 1, -1.28640000, 36.82310000,
 'Moi Avenue, Next to City Mall', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 722 111 222', '+254722111222', 'info@albarakah.co.ke', 'https://albarakah.co.ke',
 JSON_OBJECT('monday','08:00 - 22:00','tuesday','08:00 - 22:00','wednesday','08:00 - 22:00','thursday','08:00 - 22:00','friday','14:00 - 23:00','saturday','08:00 - 23:00','sunday','08:00 - 22:00'),
 JSON_ARRAY('Halal Certified','Free Wi-Fi','Parking','Family Seating','Delivery')),

(4, 'Baitul Aman Halal Butcher', 'baitul-aman-halal-butcher',
 'Certified halal butcher offering fresh beef, goat, and chicken. We also stock a wide range of halal groceries and spices.',
 'Fresh certified halal meat and groceries.',
 'normal', '$$', 1, 0, 1, -4.04350000, 39.66820000,
 'Digo Road, Opposite Mwembe Tayari', 'Mombasa', 'Mombasa County', 'Kenya',
 '+254 733 222 333', '+254733222333', 'baitulaman@gmail.com', NULL,
 JSON_OBJECT('monday','07:00 - 19:00','tuesday','07:00 - 19:00','wednesday','07:00 - 19:00','thursday','07:00 - 19:00','friday','07:00 - 19:00','saturday','07:00 - 20:00','sunday','08:00 - 18:00'),
 JSON_ARRAY('Halal Certified','Fresh Daily')),

(1, 'Noor Pharmacy', 'noor-pharmacy',
 'Community pharmacy offering prescription medicines, wellness products, and free blood-pressure checks. Licensed pharmacists on duty.',
 'Your trusted community pharmacy.',
 'normal', '$$', 1, 0, 1, -1.29210000, 36.82190000,
 'Kenyatta Avenue, Pioneer House', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 711 333 444', NULL, 'hello@noorpharmacy.co.ke', NULL,
 JSON_OBJECT('monday','08:00 - 20:00','tuesday','08:00 - 20:00','wednesday','08:00 - 20:00','thursday','08:00 - 20:00','friday','08:00 - 20:00','saturday','09:00 - 18:00','sunday','Closed'),
 JSON_ARRAY('Free BP Check','Insurance Accepted')),

(4, 'Iqra Bookstore & Islamic Gifts', 'iqra-bookstore',
 'Quran copies, Islamic books in English, Kiswahili and Arabic, prayer mats, hijabs, and gifts for all occasions.',
 'Books, Qurans and Islamic gifts for the whole family.',
 'special', '$', 1, 0, 1, -1.29000000, 36.82500000,
 'Mfangano Street', 'Nairobi', 'Nairobi County', 'Kenya',
 '+254 700 444 555', '+254700444555', 'iqrabooks@gmail.com', NULL,
 JSON_OBJECT('monday','09:00 - 18:00','tuesday','09:00 - 18:00','wednesday','09:00 - 18:00','thursday','09:00 - 18:00','friday','09:00 - 18:00','saturday','09:00 - 18:00','sunday','Closed'),
 JSON_ARRAY('Gift Wrapping','Kids Section')),

(1, 'Al-Salam Travel & Tours', 'al-salam-travel',
 'Hajj and Umrah packages, domestic safaris, and flight bookings. Our team has guided over 2,000 pilgrims.',
 'Hajj, Umrah and holiday packages you can trust.',
 'premier', '$$$', 1, 1, 1, -4.04350000, 39.66820000,
 'Nkrumah Road, Makuti House', 'Mombasa', 'Mombasa County', 'Kenya',
 '+254 755 555 666', '+254755555666', 'bookings@alsalamtravel.co.ke', 'https://alsalamtravel.co.ke',
 JSON_OBJECT('monday','08:30 - 17:30','tuesday','08:30 - 17:30','wednesday','08:30 - 17:30','thursday','08:30 - 17:30','friday','08:30 - 12:30','saturday','Closed','sunday','Closed'),
 JSON_ARRAY('Hajj & Umrah','Flight Bookings','Visa Help')),

(1, 'Green Bites Halal Cafe', 'green-bites-cafe',
 'Cozy halal cafe with fresh juices, smoothies, coffee, and light bites. Free Wi-Fi and a quiet study corner.',
 'Fresh juices, coffee & light bites in a cozy space.',
 'normal', '$', 1, 0, 1, -0.10220000, 34.76170000,
 'Oginga Odinga Street', 'Kisumu', 'Kisumu County', 'Kenya',
 '+254 729 666 777', '+254729666777', 'greenbites@gmail.com', NULL,
 JSON_OBJECT('monday','07:00 - 20:00','tuesday','07:00 - 20:00','wednesday','07:00 - 20:00','thursday','07:00 - 20:00','friday','07:00 - 20:00','saturday','08:00 - 21:00','sunday','08:00 - 18:00'),
 JSON_ARRAY('Free Wi-Fi','Halal Certified','Study Corner'));

-- business <-> category links
INSERT INTO `business_categories` (`business_id`, `category_id`, `is_primary`) VALUES
(1, 1, 1),   -- Al-Barakah → Restaurants
(2, 2, 1),   -- Baitul Aman → Shopping
(3, 4, 1),   -- Noor Pharmacy → Health & Medical
(4, 5, 1),   -- Iqra → Education
(5, 3, 1),   -- Al-Salam → Services
(6, 1, 1);   -- Green Bites → Restaurants

-- ---------- MOSQUES ----------
INSERT INTO `mosques`
(`user_id`, `name`, `slug`, `description`, `is_verified`, `latitude`, `longitude`, `address`, `city`, `region`,
 `phone`, `email`, `imam_name`, `capacity`, `facilities`) VALUES
(1, 'Jamia Mosque Nairobi', 'jamia-mosque-nairobi',
 'One of Nairobi''s most historic mosques, located in the city centre. Hosts daily prayers, jumuah, Quran classes and community iftar during Ramadan.',
 1, -1.28640000, 36.82310000, 'Banda Street, CBD', 'Nairobi', 'Nairobi County',
 '+254 20 222 101', 'info@jamiamosque.or.ke', 'Sheikh Abdullahi Mohamed', 5000,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', false, 'wheelchair', true, 'quran_classes', true, 'ramadan_iftar', true)),

(1, 'Mombasa Central Mosque', 'mombasa-central-mosque',
 'Historic seaside mosque in Old Town Mombasa with a beautiful coral-stone minaret. Friday lectures in Kiswahili and Arabic.',
 1, -4.06670000, 39.66160000, 'Old Town, Fort Jesus Road', 'Mombasa', 'Mombasa County',
 '+254 41 222 202', NULL, 'Sheikh Ali Abdalla', 2500,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', false, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', true)),

(1, 'Masjid Noor', 'masjid-noor',
 'Neighbourhood mosque with a friendly community feel. Daily classes for children and an active youth group.',
 1, -1.30530000, 36.83040000, 'Parklands, Third Avenue', 'Nairobi', 'Nairobi County',
 '+254 733 000 111', NULL, 'Imam Yusuf Otieno', 800,
 JSON_OBJECT('women_section', true, 'wudu', true, 'parking', true, 'wheelchair', true, 'quran_classes', true, 'ramadan_iftar', true)),

(1, 'Riyadha Mosque', 'riyadha-mosque',
 'Historic 19th-century mosque on Lamu Island, a UNESCO World Heritage site. Famous for the Maulidi festival.',
 1, -2.26970000, 40.90210000, 'Harambee Avenue, Lamu Old Town', 'Lamu', 'Lamu County',
 '+254 42 633 303', NULL, 'Sheikh Ahmed Badawy', 1200,
 JSON_OBJECT('women_section', false, 'wudu', true, 'parking', false, 'wheelchair', false, 'quran_classes', true, 'ramadan_iftar', true));

-- ---------- FUNDIS ----------
INSERT INTO `fundis`
(`user_id`, `profession`, `profession_other`, `years_experience`, `bio`, `is_verified`, `is_available`,
 `hourly_rate_min`, `hourly_rate_max`, `service_radius_km`, `latitude`, `longitude`, `address`, `city`, `region`,
 `phone`, `whatsapp`, `email`, `skills`, `working_hours`) VALUES
(5, 'Plumber', NULL, 12,
 'Certified plumber with 12 years experience in residential and commercial plumbing, water heaters, and drainage systems. Emergency call-outs available 24/7 within Mombasa.',
 1, 1, 500, 1500, 30, -4.04350000, 39.66820000, 'Tudor Estate, Phase 4', 'Mombasa', 'Mombasa County',
 '+254 712 888 999', '+254712888999', 'abdullahi.plumbing@gmail.com',
 JSON_ARRAY('Plumbing','Water Heaters','Drainage','Bathroom Fitting','Emergency Repairs'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 17:00','sunday','Emergency Only')),

(6, 'Tailor', NULL, 8,
 'Professional tailor specialising in kanga and kitenge dresses, suits, and school uniforms. Bulk orders for shops and offices welcome.',
 1, 1, 300, 800, 20, -1.28640000, 36.82310000, 'Eastleigh, 1st Avenue', 'Nairobi', 'Nairobi County',
 '+254 701 234 567', '+254701234567', 'fatuma.tailor@gmail.com',
 JSON_ARRAY('Tailoring','Dress Making','Suits','School Uniforms','Kanga & Kitenge'),
 JSON_OBJECT('monday','08:30 - 17:30','tuesday','08:30 - 17:30','wednesday','08:30 - 17:30','thursday','08:30 - 17:30','friday','08:30 - 12:30','saturday','09:00 - 16:00','sunday','Closed')),

(7, 'Electrician', NULL, 15,
 'Licensed electrician (EPRA certified) handling wiring, installations, solar systems, and fault finding for homes and businesses.',
 1, 1, 600, 2000, 50, -1.29210000, 36.82190000, 'South B, Mbotela Estate', 'Nairobi', 'Nairobi County',
 '+254 722 345 678', '+254722345678', 'musa.electric@gmail.com',
 JSON_ARRAY('Electrical Wiring','Solar Installation','Fault Finding','Industrial Electrical','Safety Inspections'),
 JSON_OBJECT('monday','08:00 - 18:00','tuesday','08:00 - 18:00','wednesday','08:00 - 18:00','thursday','08:00 - 18:00','friday','08:00 - 12:00','saturday','09:00 - 17:00','sunday','Closed'));

-- fundi portfolio photos
INSERT INTO `fundi_photos` (`fundi_id`, `photo_path`, `caption`, `is_portfolio`) VALUES
(1, 'assets/img/sample/fundi-plumber-1.svg', 'Bathroom plumbing renovation', 1),
(1, 'assets/img/sample/fundi-plumber-2.svg', 'Water heater installation', 1),
(2, 'assets/img/sample/fundi-tailor-1.svg', 'Kitenge dress set', 1),
(3, 'assets/img/sample/fundi-electric-1.svg', 'Solar panel wiring', 1);

-- ---------- REVIEWS ----------
INSERT INTO `reviews` (`user_id`, `reviewable_id`, `reviewable_type`, `rating`, `title`, `content`, `helpful_count`, `is_verified_visit`) VALUES
(2, 1, 'business', 5, 'Best biryani in Nairobi!', 'The chicken biryani is incredible and the family seating is very private and comfortable. Staff are so welcoming. Highly recommended for iftar too.', 12, 1),
(3, 1, 'business', 4, 'Great food, busy at peak hours', 'Delicious samosas and the mandazi are fresh all day. Gets crowded on Friday evenings - go early!', 5, 1),
(2, 2, 'business', 5, 'Trustworthy halal butcher', 'I drive across town for their goat meat. Always fresh, always properly halal, fair prices.', 8, 1),
(3, 3, 'business', 4, 'Helpful staff', 'Got my prescription filled quickly and the pharmacist explained everything clearly. Slightly long queue at lunchtime.', 3, 1),
(2, 4, 'business', 5, 'Great selection of Islamic books', 'Found a beautiful Quran for my daughter''s graduation. They even wrapped it for free. Will be back!', 6, 1),
(3, 5, 'business', 5, 'Seamless Umrah package', 'Al-Salam handled everything - flights, visa, hotel, and guidance. Our group of 8 had zero problems. Worth every shilling.', 9, 1),
(2, 6, 'business', 4, 'Cozy study spot', 'Quiet corner, fast Wi-Fi, and the mango juice is amazing. Wished they had more food options.', 2, 1),
(2, 1, 'mosque', 5, 'Beautiful and well organised', 'Jumuah khutbah is always insightful and the crowd management is excellent. The new ladies'' section is lovely.', 15, 1),
(3, 2, 'mosque', 5, 'Historic gem in Old Town', 'Praying at the Central Mosque is a spiritual experience - the architecture alone is worth visiting.', 7, 1),
(2, 3, 'mosque', 4, 'Community feel', 'Small, friendly mosque. The kids'' Quran classes are excellent and well structured.', 4, 1),
(2, 1, 'fundi', 5, 'Fixed our burst pipe fast', 'Abdullahi responded within an hour, diagnosed the issue quickly, and charged a fair rate. Very professional.', 10, 1),
(3, 2, 'fundi', 5, 'Beautiful kitenge dresses', 'Fatuma made three dresses for my daughters and they fit perfectly. Wonderful craftsmanship and lovely person.', 6, 1),
(2, 3, 'fundi', 4, 'Quality solar install', 'Musa installed our solar system neatly and explained the maintenance. Slightly delayed on the second day but worth the wait.', 3, 1);

-- review helpful votes (demo users)
INSERT INTO `review_helpful` (`review_id`, `user_id`, `is_helpful`) VALUES
(1, 3, 1), (2, 2, 1), (3, 2, 1), (4, 3, 1), (8, 3, 1), (11, 3, 1);
