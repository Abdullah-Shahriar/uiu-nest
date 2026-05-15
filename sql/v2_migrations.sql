-- =============================================
-- UIU Nest — V2 Migration + Full Data Reset
-- Run this ENTIRE file in phpMyAdmin (uiu_nest DB)
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── STEP 1: Schema Additions ────────────────────────────────────────────────

-- 1a. complaints: add submitter_id (nullable) + is_anonymous flag
ALTER TABLE `complaints`
    ADD COLUMN IF NOT EXISTS `submitter_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL if anonymous',
    ADD COLUMN IF NOT EXISTS `is_anonymous` TINYINT(1) NOT NULL DEFAULT 1;

-- 1b. property_images: ensure cover_photo syncs to properties.image_path (trigger alternative)
--     The properties table already has image_path — we use property_images.is_cover as the source.
--     No new column needed; the API will populate properties.image_path from cover image.

-- 1c. amenities: replace emoji icon column with SVG slug (keep varchar, admin stores SVG key)
--     Existing icon column stays, we just update the data below.

-- ─── STEP 2: Wipe existing seed data ─────────────────────────────────────────

DELETE FROM `room_tenants`;
DELETE FROM `listing_requirements`;
DELETE FROM `listings`;
DELETE FROM `rooms`;
DELETE FROM `property_images`;
DELETE FROM `properties`;
DELETE FROM `announcements`;
DELETE FROM `complaints`;
DELETE FROM `resident_reviews`;
DELETE FROM `student_ratings`;
DELETE FROM `saved_listings`;
DELETE FROM `applications`;
DELETE FROM `application_documents`;
DELETE FROM `application_messages`;
DELETE FROM `owner_applications`;
-- Remove all non-system users (keep only id=1 system admin if exists, we'll recreate)
DELETE FROM `users`;
DELETE FROM `allowed_domains`;

-- Reset auto_increments
ALTER TABLE `users` AUTO_INCREMENT = 1;
ALTER TABLE `properties` AUTO_INCREMENT = 1;
ALTER TABLE `rooms` AUTO_INCREMENT = 1;
ALTER TABLE `listings` AUTO_INCREMENT = 1;
ALTER TABLE `applications` AUTO_INCREMENT = 1;
ALTER TABLE `complaints` AUTO_INCREMENT = 1;
ALTER TABLE `amenities` AUTO_INCREMENT = 1;

-- ─── STEP 3: Seed Users ──────────────────────────────────────────────────────
-- Passwords are bcrypt of 'admin123' (cost 12):
-- You can generate fresh hashes via PHP: password_hash('admin123', PASSWORD_BCRYPT, ['cost'=>12])
-- Using a valid pre-generated hash for 'admin123':

INSERT INTO `allowed_domains` (`domain`) VALUES
  ('uiu.ac.bd'),
  ('bscse.uiu.ac.bd'),
  ('student.uiu.ac.bd'),
  ('gmail.com');   -- Allow gmail for owner/admin accounts

-- User 1: Abdullah Shahriar (Admin + Owner)
-- Password: admin123
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `email_verified_at`, `is_active`) VALUES
(1, 'Abdullah Shahriar', 'shahriar@gmail.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt('password') placeholder
 'admin', '+8801900000001', NOW(), 1);

-- User 2: Shahed Khan (Owner)
-- Password: admin123
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `email_verified_at`, `is_active`) VALUES
(2, 'Shahed Khan', 'shahed@gmail.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'owner', '+8801711111111', NOW(), 1);

-- User 3: Ritu Datta (Tenant / House Manager — resident of Mukul Villa)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`, `is_active`) VALUES
(3, 'Ritu Datta', 'ritu@bscse.uiu.ac.bd',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'tenant', '+8801722222222', NOW(), '011221050', 'CSE', 4, 'female', 1);

-- User 4: Demo Student (Tahsin)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`, `is_active`) VALUES
(4, 'Tahsin Faiyaz', 'tahsin@bscse.uiu.ac.bd',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'student', '+8801733333333', NOW(), '011221001', 'CSE', 3, 'male', 1);

-- ─── STEP 4: Update passwords to 'admin123' using PHP hash ───────────────────
-- NOTE: After running this SQL, run the following PHP snippet once to fix passwords:
-- <?php
--   require 'config/database.php';
--   $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost'=>12]);
--   getDB()->prepare("UPDATE users SET password_hash = ?")->execute([$hash]);
--   echo "Done: $hash";
-- Save as fix_passwords.php, run it, then delete it.

-- ─── STEP 5: Seed Amenities (SVG icon slugs) ─────────────────────────────────
INSERT INTO `amenities` (`slug`, `label`, `icon`, `sort_order`) VALUES
  ('wifi',          'High-Speed WiFi',    'wifi', 1),
  ('ac',            'Air Conditioning',   'wind', 2),
  ('attached_bath', 'Attached Bathroom',  'shower-head', 3),
  ('shared_bath',   'Shared Bathroom',    'bath', 4),
  ('furnished',     'Fully Furnished',    'sofa', 5),
  ('balcony',       'Private Balcony',    'sun', 6),
  ('parking',       'Parking Space',      'car', 7),
  ('laundry',       'Laundry Access',     'washing-machine', 8),
  ('security',      '24/7 Security',      'shield', 9),
  ('cctv',          'CCTV Surveillance',  'camera', 10),
  ('study_room',    'Study Room',         'book-open', 11),
  ('rooftop',       'Rooftop Access',     'building', 12),
  ('generator',     'Backup Generator',   'zap', 13),
  ('lift',          'Elevator/Lift',      'arrow-up', 14);

-- ─── STEP 6: Seed Properties ─────────────────────────────────────────────────

-- Owner: Shahed Khan (user id=2) — Sayeed Nagar & Satarkul
INSERT INTO `properties` (`id`, `owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`, `is_active`) VALUES
(1, 2, 'Nikunja Villa',    'Sayeed Nagar, Badda, Dhaka-1212',       23.7991, 90.4505, 'A premium gated residence in the heart of Sayeed Nagar. Features modern amenities and 24/7 security for a safe student life.', 1),
(2, 2, 'Mukul Villa',      'Sayeed Nagar, Badda, Dhaka-1212',       23.7988, 90.4508, 'Well-maintained family hostel with large rooms and a peaceful environment. Close to public transport and daily essentials.', 1),
(3, 2, 'Rupali Kuthir',    'Sayeed Nagar, Badda, Dhaka-1212',       23.7985, 90.4512, 'Compact and budget-friendly. Ideal for students who prefer a quiet, focused study environment.', 1),
(4, 2, 'Nilachol Nibash',  'Satarkul Road, Badda, Dhaka-1212',      23.7960, 90.4550, 'Spacious 6-storey building with rooftop access and CCTV coverage throughout the premises.', 1);

-- Owner: Abdullah Shahriar (user id=1) — Basundhara R/A
INSERT INTO `properties` (`id`, `owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`, `is_active`) VALUES
(5, 1, 'Tasin Villa',         'Block-D, Basundhara R/A, Dhaka-1229',  23.8120, 90.4260, 'Premium villa-style residence in Basundhara. Fully gated with generator backup, CCTV, and covered parking.', 1),
(6, 1, 'Campus Edge Hostel',  'Block-C, Basundhara R/A, Dhaka-1229',  23.8115, 90.4255, 'The go-to hostel for UIU students. Located minutes from campus with shared study lounges and fast WiFi.', 1),
(7, 1, 'Simanto Kuthir',      'Block-E, Basundhara R/A, Dhaka-1229',  23.8125, 90.4270, 'Affordable and clean. Ideal for first-year students seeking a secure, friendly hostel environment.', 1);

-- ─── STEP 7: Seed Rooms ──────────────────────────────────────────────────────

-- Nikunja Villa (property 1)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(1, '101', 2, 0, 8000.00,  '["wifi","ac","attached_bath","furnished"]', 'Spacious double room, fully AC with attached bath.'),
(1, '102', 1, 0, 6500.00,  '["wifi","ac","shared_bath"]', 'Cozy single room with fan & shared bath.');

-- Mukul Villa (property 2) — Ritu Datta is a resident here
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(2, 'A1', 2, 1, 7500.00,  '["wifi","ac","attached_bath","furnished"]', 'Comfortable double room. Ritu Datta currently resides here.'),
(2, 'A2', 1, 0, 5500.00,  '["wifi","shared_bath"]', 'Single room, quiet corner of the building.');

-- Rupali Kuthir (property 3)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(3, 'G1', 1, 0, 5000.00,  '["wifi","shared_bath"]', 'Budget single near entrance.'),
(3, 'G2', 2, 0, 7000.00,  '["wifi","ac","shared_bath"]', 'Double room with AC.');

-- Nilachol Nibash (property 4)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(4, 'B1', 2, 0, 9000.00,  '["wifi","ac","attached_bath","furnished","cctv","security"]', 'Premium double room with full security amenities.'),
(4, 'B2', 3, 0, 7000.00,  '["wifi","shared_bath","cctv"]', 'Triple-sharing room with CCTV.');

-- Tasin Villa (property 5)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(5, 'T1', 2, 0, 10000.00, '["wifi","ac","attached_bath","furnished","parking","generator"]', 'Premium double room with all facilities.'),
(5, 'T2', 1, 0, 7500.00,  '["wifi","ac","shared_bath","furnished"]', 'Single room with AC and shared bath.');

-- Campus Edge Hostel (property 6)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(6, 'C1', 1, 0, 5500.00,  '["wifi","shared_bath","study_room"]', 'Single room, study lounge access.'),
(6, 'C2', 2, 0, 8500.00,  '["wifi","ac","attached_bath","study_room"]', 'Double room with AC and attached bath.');

-- Simanto Kuthir (property 7)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(7, 'S1', 1, 0, 4500.00,  '["wifi","shared_bath"]', 'Affordable single room.'),
(7, 'S2', 2, 0, 6000.00,  '["wifi","ac","shared_bath"]', 'Double room with AC.');

-- ─── STEP 8: Seed Ritu Datta as room tenant in Mukul Villa room A1 ──────────
-- Room A1 in Mukul Villa = room id 3 (property 2, 3rd insert in rooms)
INSERT INTO `room_tenants` (`room_id`, `user_id`, `moved_in_at`) VALUES (3, 3, NOW());

-- ─── STEP 9: Seed Published Listings ─────────────────────────────────────────
-- (One listing per room for demo purposes)
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
-- Nikunja Villa
(1, 2, 'owner_direct', 'Double Room at Nikunja Villa — AC & Attached Bath', 'Fully furnished AC double room in prime Sayeed Nagar location. Perfect for two students.', 'published', NOW()),
(2, 2, 'owner_direct', 'Single Room at Nikunja Villa', 'Clean single room with WiFi. Ideal for focused students.', 'published', NOW()),
-- Mukul Villa
(4, 2, 'owner_direct', 'Single Room Available at Mukul Villa', 'Quiet single room in well-managed Mukul Villa.', 'published', NOW()),
-- Rupali Kuthir
(5, 2, 'owner_direct', 'Budget Single Room — Rupali Kuthir', 'Affordable single room near public transport.', 'published', NOW()),
(6, 2, 'owner_direct', 'Double Room at Rupali Kuthir — AC', 'Comfortable double room with AC and shared bath.', 'published', NOW()),
-- Nilachol Nibash
(7, 2, 'owner_direct', 'Premium Double Room at Nilachol Nibash', 'High-security double room with full CCTV coverage.', 'published', NOW()),
-- Tasin Villa
(9, 1, 'owner_direct', 'Premium Double Room — Tasin Villa, Basundhara', 'Top-tier room with all amenities including parking and generator.', 'published', NOW()),
-- Campus Edge Hostel
(11, 1, 'owner_direct', 'Single Room at Campus Edge Hostel', 'Minutes from UIU campus. Study lounge access included.', 'published', NOW()),
(12, 1, 'owner_direct', 'Double Room at Campus Edge — AC & Attached Bath', 'Well-ventilated double with attached bath and study room.', 'published', NOW()),
-- Simanto Kuthir
(13, 1, 'owner_direct', 'Budget Single — Simanto Kuthir, Basundhara', 'Great value single room for first-year students.', 'published', NOW());

-- ─── STEP 10: Listing Requirements ──────────────────────────────────────────
INSERT INTO `listing_requirements` (`listing_id`, `preferred_gender`, `smoking_allowed`, `is_mandatory`) VALUES
(1, 'any', 0, 0),
(2, 'any', 0, 0),
(3, 'any', 0, 0),
(4, 'any', 0, 0),
(5, 'any', 0, 0),
(6, 'any', 0, 0),
(7, 'any', 0, 0),
(8, 'any', 0, 0),
(9, 'any', 0, 0),
(10, 'any', 0, 0);

SET FOREIGN_KEY_CHECKS = 1;
