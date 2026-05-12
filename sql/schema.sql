-- =============================================
-- UIU Nest — Off-Campus Housing Platform
-- Database Schema (MySQL / MariaDB)
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 1. USERS
-- ---------------------------------------------
CREATE TABLE `users` (
  `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `full_name`           VARCHAR(120)    NOT NULL,
  `email`               VARCHAR(255)    NOT NULL,
  `password_hash`       VARCHAR(255)    NOT NULL,
  `phone`               VARCHAR(20)     DEFAULT NULL,
  `role`                ENUM('guest','student','tenant','owner','admin')
                                        NOT NULL DEFAULT 'student',
  `email_verified_at`   DATETIME        DEFAULT NULL,
  `avatar_path`         VARCHAR(500)    DEFAULT NULL,
  `student_id`          VARCHAR(30)     DEFAULT NULL,
  `department`          VARCHAR(100)    DEFAULT NULL,
  `year_of_study`       TINYINT UNSIGNED DEFAULT NULL,
  `gender`              ENUM('male','female','other') DEFAULT NULL,
  `bio`                 TEXT            DEFAULT NULL,
  `is_active`           TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 2. ALLOWED EMAIL DOMAINS
-- ---------------------------------------------
CREATE TABLE `allowed_domains` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `domain`      VARCHAR(255)    NOT NULL,
  `added_by`    INT UNSIGNED    DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 3. AMENITIES (admin-managed, custom icons)
-- ---------------------------------------------
CREATE TABLE `amenities` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(50)     NOT NULL,
  `label`       VARCHAR(100)    NOT NULL,
  `icon`        VARCHAR(100)    NOT NULL DEFAULT '✨',
  `sort_order`  INT             NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_amenity_slug` (`slug`),
  INDEX `idx_amenity_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 4. PROPERTIES (Hostels)
-- ---------------------------------------------
CREATE TABLE `properties` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `owner_id`        INT UNSIGNED    NOT NULL,
  `name`            VARCHAR(200)    NOT NULL,
  `address`         TEXT            NOT NULL,
  `location_lat`    DECIMAL(10,7)   NOT NULL,
  `location_lng`    DECIMAL(10,7)   NOT NULL,
  `description`     TEXT            DEFAULT NULL,
  `image_path`      VARCHAR(500)    DEFAULT NULL,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prop_owner` (`owner_id`),
  INDEX `idx_prop_location` (`location_lat`, `location_lng`),
  CONSTRAINT `fk_prop_owner` FOREIGN KEY (`owner_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 5. ROOMS
-- ---------------------------------------------
CREATE TABLE `rooms` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `property_id`       INT UNSIGNED    NOT NULL,
  `room_number`       VARCHAR(20)     NOT NULL,
  `capacity`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `current_occupancy` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `rent_amount`       DECIMAL(10,2)   NOT NULL,
  `amenities_json`    JSON            DEFAULT NULL,
  `description`       TEXT            DEFAULT NULL,
  `image_path`        VARCHAR(500)    DEFAULT NULL,
  `is_active`         TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_property` (`property_id`),
  INDEX `idx_room_rent` (`rent_amount`),
  CONSTRAINT `fk_room_property` FOREIGN KEY (`property_id`)
    REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 6. ROOM TENANTS (junction)
-- ---------------------------------------------
CREATE TABLE `room_tenants` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `room_id`     INT UNSIGNED    NOT NULL,
  `user_id`     INT UNSIGNED    NOT NULL,
  `moved_in_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `moved_out_at` DATETIME       DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_room_user_active` (`room_id`, `user_id`, `moved_out_at`),
  INDEX `idx_rt_user` (`user_id`),
  CONSTRAINT `fk_rt_room` FOREIGN KEY (`room_id`)
    REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 7. LISTINGS
-- ---------------------------------------------
CREATE TABLE `listings` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `room_id`       INT UNSIGNED    NOT NULL,
  `created_by`    INT UNSIGNED    NOT NULL,
  `listing_type`  ENUM('owner_direct','roommate_needed')
                                  NOT NULL,
  `title`         VARCHAR(255)    NOT NULL,
  `description`   TEXT            DEFAULT NULL,
  `status`        ENUM('draft','pending_owner_approval','published','closed','rejected')
                                  NOT NULL DEFAULT 'draft',
  `published_at`  DATETIME        DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`    DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_listing_room` (`room_id`),
  INDEX `idx_listing_creator` (`created_by`),
  INDEX `idx_listing_status` (`status`),
  INDEX `idx_listing_type` (`listing_type`),
  INDEX `idx_listing_deleted` (`deleted_at`),
  CONSTRAINT `fk_listing_room` FOREIGN KEY (`room_id`)
    REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_listing_creator` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 8. LISTING REQUIREMENTS
-- ---------------------------------------------
CREATE TABLE `listing_requirements` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `listing_id`        INT UNSIGNED    NOT NULL,
  `preferred_gender`  ENUM('male','female','any')   DEFAULT 'any',
  `preferred_dept`    VARCHAR(100)    DEFAULT NULL,
  `min_year`          TINYINT UNSIGNED DEFAULT NULL,
  `max_year`          TINYINT UNSIGNED DEFAULT NULL,
  `smoking_allowed`   TINYINT(1)      DEFAULT 1,
  `pets_allowed`      TINYINT(1)      DEFAULT 1,
  `is_mandatory`      TINYINT(1)      NOT NULL DEFAULT 0,
  `custom_json`       JSON            DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_listing` (`listing_id`),
  CONSTRAINT `fk_req_listing` FOREIGN KEY (`listing_id`)
    REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 9. APPLICATIONS
-- ---------------------------------------------
CREATE TABLE `applications` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `listing_id`      INT UNSIGNED    NOT NULL,
  `applicant_id`    INT UNSIGNED    NOT NULL,
  `status`          ENUM('pending_tenant_review','pending_owner_review','accepted','enrolled','rejected_by_tenant','rejected_by_owner','withdrawn')
                                    NOT NULL DEFAULT 'pending_owner_review',
  `cover_message`   TEXT            DEFAULT NULL,
  `application_date` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_listing_applicant` (`listing_id`, `applicant_id`),
  INDEX `idx_app_applicant` (`applicant_id`),
  INDEX `idx_app_status` (`status`),
  INDEX `idx_app_deleted` (`deleted_at`),
  CONSTRAINT `fk_app_listing` FOREIGN KEY (`listing_id`)
    REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_applicant` FOREIGN KEY (`applicant_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 10. APPLICATION DOCUMENTS (verification docs submitted with application)
-- ---------------------------------------------
CREATE TABLE `application_documents` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `application_id`  INT UNSIGNED    NOT NULL,
  `applicant_id`    INT UNSIGNED    NOT NULL,
  `id_card_front`   VARCHAR(500)    NOT NULL,
  `id_card_back`    VARCHAR(500)    NOT NULL,
  `selfie_path`     VARCHAR(500)    NOT NULL,
  `video_path`      VARCHAR(500)    NOT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_appdoc_app` (`application_id`),
  INDEX `idx_appdoc_user` (`applicant_id`),
  CONSTRAINT `fk_appdoc_app` FOREIGN KEY (`application_id`)
    REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appdoc_user` FOREIGN KEY (`applicant_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 11. APPLICATION MESSAGES
-- ---------------------------------------------
CREATE TABLE `application_messages` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `application_id`  INT UNSIGNED    NOT NULL,
  `sender_id`       INT UNSIGNED    NOT NULL,
  `message`         TEXT            NOT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_msg_app` (`application_id`),
  CONSTRAINT `fk_msg_app` FOREIGN KEY (`application_id`)
    REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 12. SAVED LISTINGS (Shortlist / Favorites)
-- ---------------------------------------------
CREATE TABLE `saved_listings` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NOT NULL,
  `listing_id`  INT UNSIGNED    NOT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_saved` (`user_id`, `listing_id`),
  INDEX `idx_saved_listing` (`listing_id`),
  CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_listing` FOREIGN KEY (`listing_id`)
    REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 13. PROPERTY IMAGES (owner-uploaded photos)
-- ---------------------------------------------
CREATE TABLE `property_images` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `property_id`   INT UNSIGNED    NOT NULL,
  `image_path`    VARCHAR(500)    NOT NULL,
  `is_cover`      TINYINT(1)      NOT NULL DEFAULT 0,
  `sort_order`    INT             NOT NULL DEFAULT 0,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_propimg_property` (`property_id`),
  CONSTRAINT `fk_propimg_property` FOREIGN KEY (`property_id`)
    REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 14. OWNER APPLICATIONS (Review Queue)
-- ---------------------------------------------
CREATE TABLE `owner_applications` (
  `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `full_name`             VARCHAR(255)    NOT NULL,
  `email`                 VARCHAR(255)    NOT NULL,
  `phone`                 VARCHAR(20)     NOT NULL,
  `address`               TEXT            NOT NULL,
  `nid_path`              VARCHAR(500)    NOT NULL,
  `electricity_bill_path` VARCHAR(500)    NOT NULL,
  `photo_path`            VARCHAR(500)    NOT NULL,
  `extra_info`            TEXT,
  `status`                ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEED DATA
-- =============================================

-- Default allowed university email domains
INSERT INTO `allowed_domains` (`domain`) VALUES
  ('uiu.ac.bd'),
  ('bscse.uiu.ac.bd'),
  ('student.uiu.ac.bd');


-- Seed amenities (admin can add/edit more via panel)
INSERT INTO `amenities` (`slug`, `label`, `icon`, `sort_order`) VALUES
  ('wifi',          'High-Speed WiFi',    '📶', 1),
  ('ac',            'Air Conditioning',   '❄️', 2),
  ('attached_bath', 'Attached Bathroom',  '🚿', 3),
  ('shared_bath',   'Shared Bathroom',    '🛁', 4),
  ('furnished',     'Fully Furnished',    '🪑', 5),
  ('balcony',       'Private Balcony',    '🌇', 6),
  ('parking',       'Parking Space',      '🅿️', 7),
  ('laundry',       'Laundry Access',     '👕', 8),
  ('security',      '24/7 Security',      '🔒', 9),
  ('cctv',          'CCTV Surveillance',  '📹', 10),
  ('study_room',    'Study Room',         '📚', 11),
  ('rooftop',       'Rooftop Access',     '🏙️', 12);

-- Default admin user (password set via PHP script after import)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `email_verified_at`)
VALUES ('System Admin', 'admin@uiu.ac.bd',
  '$2y$12$placeholder_will_be_fixed_by_setup_script_000000000000000',
  'admin', NOW());

-- Demo owner (Shahed Khan)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `email_verified_at`, `phone`)
VALUES ('Shahed Khan', 'shahed@gmail.com',
  '$2y$12$placeholder_will_be_fixed_by_setup_script_000000000000000',
  'owner', NOW(), '+8801711111111');

-- Demo house manager / tenant (Ritu Datta)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`)
VALUES ('Ritu Datta', 'ritu@bscse.uiu.ac.bd',
  '$2y$12$placeholder_will_be_fixed_by_setup_script_000000000000000',
  'tenant', NOW(), '011221050', 'CSE', 4, 'female');

-- Demo applicant / student (Tahsin Faiyaz)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`)
VALUES ('Tahsin Faiyaz', 'tahsin@bscse.uiu.ac.bd',
  '$2y$12$placeholder_will_be_fixed_by_setup_script_000000000000000',
  'student', NOW(), '011221001', 'CSE', 3, 'male');

-- Demo properties near UIU
INSERT INTO `properties` (`owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`) VALUES
  (2, 'Greenview Residence', '15/A Madani Avenue, Badda, Dhaka', 23.7995, 90.4510, 'Modern student housing just 5 minutes walk from UIU campus. Fully furnished rooms with 24/7 security.'),
  (2, 'Scholar Heights', '22 Bir Uttam Rafiqul Islam Ave, Dhaka', 23.7960, 90.4480, 'Premium accommodation for university students. Rooftop study lounge, high-speed internet, and backup generator.'),
  (2, 'Campus Edge Hostel', 'United City, Madani Avenue, Dhaka', 23.8005, 90.4520, 'Closest hostel to UIU campus. Walking distance, affordable rooms with modern amenities.');

-- Demo rooms
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
  (1, '101', 2, 1, 8500.00, '["wifi","ac","attached_bath","furnished"]', 'Spacious double room with balcony facing the garden.'),
  (1, '102', 1, 0, 6000.00, '["wifi","fan","shared_bath"]', 'Cozy single room ideal for focused study.'),
  (1, '201', 3, 2, 5500.00, '["wifi","fan","shared_bath","furnished"]', 'Triple sharing room with large windows.'),
  (2, 'A1', 2, 0, 9500.00, '["wifi","ac","attached_bath","furnished","balcony"]', 'Premium double room with city view.'),
  (2, 'A2', 2, 1, 7500.00, '["wifi","ac","shared_bath","furnished"]', 'Comfortable shared room on the quiet side.'),
  (3, 'G1', 1, 0, 5000.00, '["wifi","fan","shared_bath"]', 'Budget-friendly single near campus gate.'),
  (3, 'G2', 2, 0, 7000.00, '["wifi","ac","attached_bath"]', 'Well-ventilated double room with study desk.');

-- Demo tenant in room 101
INSERT INTO `room_tenants` (`room_id`, `user_id`) VALUES (1, 3);

-- Demo listings
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
  (1, 3, 'roommate_needed', 'Looking for a Roommate - Greenview 101', 'Need a chill, studious roommate for the spring semester. Room is fully furnished with AC. Rent split equally.', 'published', NOW()),
  (4, 2, 'owner_direct', 'Premium Double Room at Scholar Heights', 'Brand new fully furnished double room with AC, attached bath, and balcony. Available from next month.', 'published', NOW()),
  (6, 2, 'owner_direct', 'Budget Single Room - Campus Edge', 'Affordable single room just 2 minutes from UIU gate. Perfect for freshers.', 'published', NOW()),
  (7, 2, 'owner_direct', 'Double Room Near Campus - AC & Attached Bath', 'Well-maintained double room with AC and attached bathroom. Study desk included.', 'published', NOW()),
  (2, 2, 'owner_direct', 'Single Room at Greenview', 'Quiet single room with WiFi. Shared bathroom. Great for introverts.', 'published', NOW());

-- Demo requirements
INSERT INTO `listing_requirements` (`listing_id`, `preferred_gender`, `preferred_dept`, `min_year`, `max_year`, `smoking_allowed`, `is_mandatory`) VALUES
  (1, 'male', 'CSE', 2, 4, 0, 1),
  (2, 'any', NULL, NULL, NULL, 1, 0),
  (3, 'any', NULL, NULL, NULL, 1, 0),
  (4, 'any', NULL, NULL, NULL, 0, 0),
  (5, 'any', NULL, NULL, NULL, 1, 0);

SET FOREIGN_KEY_CHECKS = 1;