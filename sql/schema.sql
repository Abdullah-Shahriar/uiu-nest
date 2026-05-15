-- =============================================
-- UIU Nest v2 — Database Schema
-- Run this in phpMyAdmin on a FRESH/empty database.
-- After import, visit /test_auth.php to set passwords.
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 1. USERS
-- ---------------------------------------------
CREATE TABLE `users` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `full_name`         VARCHAR(120)    NOT NULL,
  `email`             VARCHAR(255)    NOT NULL,
  `password_hash`     VARCHAR(255)    NOT NULL,
  `phone`             VARCHAR(20)     DEFAULT NULL,
  `role`              ENUM('guest','student','tenant','owner','admin') NOT NULL DEFAULT 'student',
  `email_verified_at` DATETIME        DEFAULT NULL,
  `avatar_path`       VARCHAR(500)    DEFAULT NULL,
  `student_id`        VARCHAR(30)     DEFAULT NULL,
  `department`        VARCHAR(100)    DEFAULT NULL,
  `year_of_study`     TINYINT UNSIGNED DEFAULT NULL,
  `gender`            ENUM('male','female','other') DEFAULT NULL,
  `bio`               TEXT            DEFAULT NULL,
  `is_active`         TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 2. ALLOWED EMAIL DOMAINS
-- ---------------------------------------------
CREATE TABLE `allowed_domains` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain`     VARCHAR(255) NOT NULL,
  `added_by`   INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 3. AMENITIES
-- ---------------------------------------------
CREATE TABLE `amenities` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(50)  NOT NULL,
  `label`      VARCHAR(100) NOT NULL,
  `icon`       VARCHAR(100) NOT NULL DEFAULT 'star',
  `sort_order` INT          NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_amenity_slug` (`slug`),
  INDEX `idx_amenity_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 4. PROPERTIES
-- ---------------------------------------------
CREATE TABLE `properties` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `owner_id`        INT UNSIGNED  NOT NULL,
  `house_manager_id` INT UNSIGNED DEFAULT NULL,
  `name`            VARCHAR(200)  NOT NULL,
  `address`         TEXT          NOT NULL,
  `location_lat`    DECIMAL(10,7) NOT NULL DEFAULT 23.7990,
  `location_lng`    DECIMAL(10,7) NOT NULL DEFAULT 90.4510,
  `description`     TEXT          DEFAULT NULL,
  `image_path`      VARCHAR(500)  DEFAULT NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prop_owner` (`owner_id`),
  INDEX `idx_prop_location` (`location_lat`, `location_lng`),
  CONSTRAINT `fk_prop_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 5. ROOMS
-- ---------------------------------------------
CREATE TABLE `rooms` (
  `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `property_id`       INT UNSIGNED     NOT NULL,
  `room_number`       VARCHAR(20)      NOT NULL,
  `capacity`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `current_occupancy` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `rent_amount`       DECIMAL(10,2)    NOT NULL,
  `amenities_json`    JSON             DEFAULT NULL,
  `description`       TEXT             DEFAULT NULL,
  `image_path`        VARCHAR(500)     DEFAULT NULL,
  `is_active`         TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_property` (`property_id`),
  INDEX `idx_room_rent` (`rent_amount`),
  CONSTRAINT `fk_room_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 6. ROOM TENANTS
-- ---------------------------------------------
CREATE TABLE `room_tenants` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED NOT NULL,
  `user_id`      INT UNSIGNED NOT NULL,
  `moved_in_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moved_out_at` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_rt_user` (`user_id`),
  CONSTRAINT `fk_rt_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 7. LISTINGS
-- ---------------------------------------------
CREATE TABLE `listings` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`      INT UNSIGNED NOT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `listing_type` ENUM('owner_direct','roommate_needed') NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `status`       ENUM('draft','pending_owner_approval','published','closed','rejected') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`   DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_listing_room` (`room_id`),
  INDEX `idx_listing_creator` (`created_by`),
  INDEX `idx_listing_status` (`status`),
  INDEX `idx_listing_deleted` (`deleted_at`),
  CONSTRAINT `fk_listing_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_listing_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 8. LISTING REQUIREMENTS
-- ---------------------------------------------
CREATE TABLE `listing_requirements` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `listing_id`       INT UNSIGNED     NOT NULL,
  `preferred_gender` ENUM('male','female','any') DEFAULT 'any',
  `preferred_dept`   VARCHAR(100)     DEFAULT NULL,
  `min_year`         TINYINT UNSIGNED DEFAULT NULL,
  `max_year`         TINYINT UNSIGNED DEFAULT NULL,
  `smoking_allowed`  TINYINT(1)       DEFAULT 1,
  `pets_allowed`     TINYINT(1)       DEFAULT 1,
  `is_mandatory`     TINYINT(1)       NOT NULL DEFAULT 0,
  `custom_json`      JSON             DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_listing` (`listing_id`),
  CONSTRAINT `fk_req_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 9. APPLICATIONS
-- ---------------------------------------------
CREATE TABLE `applications` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id`       INT UNSIGNED NOT NULL,
  `applicant_id`     INT UNSIGNED NOT NULL,
  `status`           ENUM('pending_owner_review','accepted','enrolled','rejected_by_owner','withdrawn') NOT NULL DEFAULT 'pending_owner_review',
  `cover_message`    TEXT         DEFAULT NULL,
  `applied_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`       DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_listing_applicant` (`listing_id`, `applicant_id`),
  INDEX `idx_app_applicant` (`applicant_id`),
  INDEX `idx_app_status` (`status`),
  CONSTRAINT `fk_app_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 10. APPLICATION MESSAGES
-- ---------------------------------------------
CREATE TABLE `application_messages` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `sender_id`      INT UNSIGNED NOT NULL,
  `message`        TEXT         NOT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_msg_app` (`application_id`),
  CONSTRAINT `fk_msg_app`    FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`)      REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 11. SAVED LISTINGS
-- ---------------------------------------------
CREATE TABLE `saved_listings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `listing_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_saved` (`user_id`, `listing_id`),
  CONSTRAINT `fk_saved_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 12. PROPERTY IMAGES
-- ---------------------------------------------
CREATE TABLE `property_images` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `image_path`  VARCHAR(500) NOT NULL,
  `is_cover`    TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_propimg_property` (`property_id`),
  CONSTRAINT `fk_propimg_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 13. COMPLAINTS (admin-only inbox)
-- ---------------------------------------------
CREATE TABLE `complaints` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category`     ENUM('maintenance','noise','safety','management','other') NOT NULL DEFAULT 'other',
  `property_id`  INT UNSIGNED DEFAULT NULL,
  `subject`      VARCHAR(255) NOT NULL,
  `description`  TEXT         NOT NULL,
  `submitter_id` INT UNSIGNED DEFAULT NULL,
  `is_anonymous` TINYINT(1)   NOT NULL DEFAULT 1,
  `status`       ENUM('open','under_review','resolved','dismissed') NOT NULL DEFAULT 'open',
  `admin_note`   TEXT         DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`  DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_complaint_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 14. ANNOUNCEMENTS / CALENDAR EVENTS
-- ---------------------------------------------
CREATE TABLE `announcements` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `created_by`  INT UNSIGNED NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `type`        ENUM('announcement','rent_due','maintenance','other') NOT NULL DEFAULT 'announcement',
  `event_date`  DATE         NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ann_property` (`property_id`),
  INDEX `idx_ann_date` (`event_date`),
  CONSTRAINT `fk_ann_property`  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ann_createdby` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 15. OWNER APPLICATIONS (review queue)
-- ---------------------------------------------
CREATE TABLE `owner_applications` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`             VARCHAR(255) NOT NULL,
  `email`                 VARCHAR(255) NOT NULL,
  `phone`                 VARCHAR(20)  NOT NULL,
  `address`               TEXT         NOT NULL,
  `nid_path`              VARCHAR(500) NOT NULL,
  `electricity_bill_path` VARCHAR(500) NOT NULL,
  `photo_path`            VARCHAR(500) NOT NULL,
  `extra_info`            TEXT,
  `status`                ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA
-- NOTE: passwords are placeholder hashes.
-- Visit /test_auth.php after import to fix them.
-- =============================================

-- Allowed email domains (gmail added for owners)
INSERT INTO `allowed_domains` (`domain`) VALUES
  ('uiu.ac.bd'),
  ('bscse.uiu.ac.bd'),
  ('student.uiu.ac.bd'),
  ('gmail.com');

-- Amenities (SVG slug icons — no emojis)
INSERT INTO `amenities` (`slug`, `label`, `icon`, `sort_order`) VALUES
  ('wifi',          'High-Speed WiFi',   'wifi',      1),
  ('ac',            'Air Conditioning',  'wind',      2),
  ('attached_bath', 'Attached Bathroom', 'droplets',  3),
  ('shared_bath',   'Shared Bathroom',   'bath',      4),
  ('furnished',     'Fully Furnished',   'sofa',      5),
  ('balcony',       'Private Balcony',   'sun',       6),
  ('parking',       'Parking Space',     'car',       7),
  ('laundry',       'Laundry Access',    'shirt',     8),
  ('security',      '24/7 Security',     'shield',    9),
  ('cctv',          'CCTV Surveillance', 'camera',    10),
  ('study_room',    'Study Room',        'book-open', 11),
  ('rooftop',       'Rooftop Access',    'building',  12),
  ('generator',     'Backup Generator',  'zap',       13),
  ('lift',          'Elevator / Lift',   'arrow-up',  14);

-- ── Users ──────────────────────────────────────────────────────
-- id=1  Admin (Abdullah Shahriar)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified_at`)
VALUES ('Abdullah Shahriar', 'admin@uiu.ac.bd',
  '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
  'admin', 1, NOW());

-- id=2  Owner (Shahed Khan)  — Sayeed Nagar properties
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified_at`, `phone`)
VALUES ('Shahed Khan', 'shahed@gmail.com',
  '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
  'owner', 1, NOW(), '+8801711111111');

-- id=3  House Manager (Ritu Datta)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`)
VALUES ('Ritu Datta', 'ritu@bscse.uiu.ac.bd',
  '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
  'tenant', 1, NOW(), '011221050', 'CSE', 4, 'female');

-- id=4  Student (Tahsin Faiyaz)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`)
VALUES ('Tahsin Faiyaz', 'tahsin@bscse.uiu.ac.bd',
  '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
  'student', 1, NOW(), '011221001', 'CSE', 3, 'male');

-- ── Properties ─────────────────────────────────────────────────
-- Shahed Khan (id=2) — Sayeed Nagar / Satarkul area
INSERT INTO `properties` (`owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`) VALUES
  (2, 'Nikunja Villa',   'Sayeed Nagar, Badda, Dhaka-1212',   23.7991, 90.4505, 'Premium gated residence in Sayeed Nagar. 24/7 security, CCTV, rooftop access.'),
  (2, 'Mukul Villa',     'Sayeed Nagar, Badda, Dhaka-1212',   23.7988, 90.4508, 'Family-style hostel with large rooms. Peaceful environment, close to transport.'),
  (2, 'Rupali Kuthir',   'Sayeed Nagar, Badda, Dhaka-1212',   23.7985, 90.4512, 'Budget-friendly compact rooms. Ideal for students focused on studies.'),
  (2, 'Nilachol Nibash', 'Satarkul Road, Badda, Dhaka-1212',  23.7960, 90.4550, '6-storey building with rooftop, CCTV, and laundry access.');

-- Abdullah Shahriar (admin id=1) — Basundhara R/A
INSERT INTO `properties` (`owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`) VALUES
  (1, 'Tasin Villa',        'Block-D, Basundhara R/A, Dhaka-1229', 23.8120, 90.4260, 'Premium villa-style residence. Generator backup, CCTV, covered parking.'),
  (1, 'Campus Edge Hostel', 'Block-C, Basundhara R/A, Dhaka-1229', 23.8115, 90.4255, 'Popular hostel with shared study lounges and fast WiFi for UIU students.'),
  (1, 'Simanto Kuthir',     'Block-E, Basundhara R/A, Dhaka-1229', 23.8125, 90.4270, 'Affordable and clean. Ideal for first-year students.');

-- ── Rooms ──────────────────────────────────────────────────────
-- Nikunja Villa (property_id=1)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (1, '101', 2, 0, 8000.00, '["wifi","ac","attached_bath","furnished"]',           'Double room, AC, attached bath.'),
  (1, '102', 1, 0, 6500.00, '["wifi","ac","shared_bath"]',                         'Single room with AC, shared bath.');

-- Mukul Villa (property_id=2)  — Room A1 occupied by Ritu Datta
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (2, 'A1',  2, 1, 7500.00, '["wifi","ac","attached_bath","furnished"]',           'Double room — Ritu Datta resides here.'),
  (2, 'A2',  1, 0, 5500.00, '["wifi","shared_bath"]',                              'Single room, quiet corner.');

-- Rupali Kuthir (property_id=3)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (3, 'G1',  1, 0, 5000.00, '["wifi","shared_bath"]',                              'Budget single near entrance.'),
  (3, 'G2',  2, 0, 7000.00, '["wifi","ac","shared_bath"]',                         'Double room with AC.');

-- Nilachol Nibash (property_id=4)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (4, 'B1',  2, 0, 9000.00, '["wifi","ac","attached_bath","furnished","cctv","security"]', 'Premium double, full security.'),
  (4, 'B2',  3, 0, 7000.00, '["wifi","shared_bath","cctv"]',                               'Triple-sharing with CCTV.');

-- Tasin Villa (property_id=5)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (5, 'T1',  2, 0, 10000.00, '["wifi","ac","attached_bath","furnished","parking","generator"]', 'Premium double, all facilities.'),
  (5, 'T2',  1, 0,  7500.00, '["wifi","ac","shared_bath","furnished"]',                         'Single room with AC.');

-- Campus Edge Hostel (property_id=6)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (6, 'C1',  1, 0, 5500.00, '["wifi","shared_bath","study_room"]',                'Single, study lounge access.'),
  (6, 'C2',  2, 0, 8500.00, '["wifi","ac","attached_bath","study_room"]',         'Double room with AC and attached bath.');

-- Simanto Kuthir (property_id=7)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (7, 'S1',  1, 0, 4500.00, '["wifi","shared_bath"]',                             'Affordable single room.'),
  (7, 'S2',  2, 0, 6000.00, '["wifi","ac","shared_bath"]',                        'Double room with AC.');

-- ── Room Tenants ───────────────────────────────────────────────
-- Ritu Datta (id=3) lives in Mukul Villa Room A1 (room_id=3)
INSERT INTO `room_tenants` (`room_id`, `user_id`, `moved_in_at`) VALUES (3, 3, NOW());

-- ── Listings ───────────────────────────────────────────────────
-- Nikunja Villa rooms
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (1, 2, 'owner_direct', 'Double Room at Nikunja Villa — AC & Attached Bath',
   'Spacious double room with full AC and attached bathroom. Available immediately.', 'published', NOW()),
  (2, 2, 'owner_direct', 'Single Room at Nikunja Villa',
   'Cozy single with AC and shared bath. Quiet building, 24/7 security.', 'published', NOW());

-- Mukul Villa — A2 only (A1 is occupied)
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (4, 2, 'owner_direct', 'Single Room Available at Mukul Villa',
   'Single room, WiFi included, quiet corner of the building.', 'published', NOW());

-- Rupali Kuthir
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (5, 2, 'owner_direct', 'Budget Single Room — Rupali Kuthir',
   'Most affordable option in Sayeed Nagar. WiFi, shared bath.', 'published', NOW()),
  (6, 2, 'owner_direct', 'Double Room at Rupali Kuthir — AC',
   'Double room with AC, shared bath. Good value for money.', 'published', NOW());

-- Nilachol Nibash
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (7, 2, 'owner_direct', 'Premium Double Room at Nilachol Nibash',
   'Fully secured double room with CCTV and 24/7 security guard.', 'published', NOW()),
  (8, 2, 'owner_direct', 'Triple Sharing Room — Nilachol Nibash',
   'Great for groups of 3. CCTV covered, rooftop access.', 'published', NOW());

-- Tasin Villa
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (9,  1, 'owner_direct', 'Premium Double Room — Tasin Villa, Basundhara',
   'Top-tier room with parking, generator, and all amenities inside Basundhara R/A.', 'published', NOW()),
  (10, 1, 'owner_direct', 'Single Room at Tasin Villa',
   'AC single room in a premium gated community.', 'published', NOW());

-- Campus Edge Hostel
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (11, 1, 'owner_direct', 'Single Room at Campus Edge Hostel',
   'Affordable single with study lounge access. Popular among UIU students.', 'published', NOW()),
  (12, 1, 'owner_direct', 'Double Room — Campus Edge, AC & Attached Bath',
   'Well-maintained double room with AC and attached bathroom.', 'published', NOW());

-- Simanto Kuthir
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (13, 1, 'owner_direct', 'Budget Single — Simanto Kuthir, Basundhara',
   'Most affordable Basundhara option. WiFi, shared bath.', 'published', NOW()),
  (14, 1, 'owner_direct', 'Double Room at Simanto Kuthir',
   'AC double room in a clean, friendly hostel.', 'published', NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- AFTER IMPORT:
--   All 4 users have password: admin123
--   (real bcrypt hash — no extra script needed)
--
--   Optionally visit /test_auth.php to:
--     - Create upload directories
--     - Verify login works
--
--   Login credentials:
--     admin@uiu.ac.bd         → admin123  (Admin)
--     shahed@gmail.com        → admin123  (Owner)
--     ritu@bscse.uiu.ac.bd   → admin123  (House Manager)
--     tahsin@bscse.uiu.ac.bd → admin123  (Student)
-- =============================================