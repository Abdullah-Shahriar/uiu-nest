-- =============================================
--  UIU Nest — Complete Database Schema
--  Single-file install. Import this into a FRESH
--  empty database in phpMyAdmin (or via CLI).
--
--  Includes all tables, columns, and seed data
--  from all previous migrations (v1 → v3).
--
--  Credentials after import (password: admin123)
--    admin@uiu.ac.bd          → System Admin
--    shahriar@gmail.com       → Abdullah Shahriar (Owner)
--    shahed@gmail.com         → Shahed Khan (Owner)
--    ritu@bscse.uiu.ac.bd    → Ritu Datta (Tenant / House Manager)
--    tahsin@bscse.uiu.ac.bd  → Tahsin Faiyaz (Student)
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────
--  1. USERS
-- ─────────────────────────────────────────────
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

-- ─────────────────────────────────────────────
--  2. ALLOWED EMAIL DOMAINS
-- ─────────────────────────────────────────────
CREATE TABLE `allowed_domains` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain`     VARCHAR(255) NOT NULL,
  `added_by`   INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  3. AMENITIES
-- ─────────────────────────────────────────────
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

-- ─────────────────────────────────────────────
--  4. PROPERTIES
--     house_manager_id  — added in migrations.sql (v1)
--     cover_photo_position — added in v3_migrations.sql
-- ─────────────────────────────────────────────
CREATE TABLE `properties` (
  `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `owner_id`             INT UNSIGNED  NOT NULL,
  `house_manager_id`     INT UNSIGNED  DEFAULT NULL,
  `name`                 VARCHAR(200)  NOT NULL,
  `address`              TEXT          NOT NULL,
  `location_lat`         DECIMAL(10,7) NOT NULL DEFAULT 23.7990,
  `location_lng`         DECIMAL(10,7) NOT NULL DEFAULT 90.4510,
  `description`          TEXT          DEFAULT NULL,
  `image_path`           VARCHAR(500)  DEFAULT NULL,
  `cover_photo_position` VARCHAR(30)   NOT NULL DEFAULT '50% 50%'
                           COMMENT 'CSS object-position for draggable cover crop',
  `is_active`            TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prop_owner`    (`owner_id`),
  INDEX `idx_prop_manager`  (`house_manager_id`),
  INDEX `idx_prop_location` (`location_lat`, `location_lng`),
  CONSTRAINT `fk_prop_owner`   FOREIGN KEY (`owner_id`)         REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prop_manager` FOREIGN KEY (`house_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  5. ROOMS
-- ─────────────────────────────────────────────
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
  INDEX `idx_room_rent`     (`rent_amount`),
  CONSTRAINT `fk_room_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  6. ROOM TENANTS
-- ─────────────────────────────────────────────
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

-- ─────────────────────────────────────────────
--  7. LISTINGS
-- ─────────────────────────────────────────────
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
  INDEX `idx_listing_room`    (`room_id`),
  INDEX `idx_listing_creator` (`created_by`),
  INDEX `idx_listing_status`  (`status`),
  INDEX `idx_listing_deleted` (`deleted_at`),
  CONSTRAINT `fk_listing_room`    FOREIGN KEY (`room_id`)    REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_listing_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  8. LISTING REQUIREMENTS
-- ─────────────────────────────────────────────
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

-- ─────────────────────────────────────────────
--  9. APPLICATIONS
--     status enum expanded to support two-step flow (v1)
-- ─────────────────────────────────────────────
CREATE TABLE `applications` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id`    INT UNSIGNED NOT NULL,
  `applicant_id`  INT UNSIGNED NOT NULL,
  `status`        ENUM(
                    'pending_tenant_review',
                    'pending_owner_review',
                    'accepted',
                    'enrolled',
                    'rejected_by_tenant',
                    'rejected_by_owner',
                    'withdrawn'
                  ) NOT NULL DEFAULT 'pending_owner_review',
  `cover_message` TEXT         DEFAULT NULL,
  `applied_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`    DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_listing_applicant` (`listing_id`, `applicant_id`),
  INDEX `idx_app_applicant` (`applicant_id`),
  INDEX `idx_app_status`    (`status`),
  CONSTRAINT `fk_app_listing`   FOREIGN KEY (`listing_id`)   REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `users`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  10. APPLICATION MESSAGES
-- ─────────────────────────────────────────────
CREATE TABLE `application_messages` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `sender_id`      INT UNSIGNED NOT NULL,
  `message`        TEXT         NOT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_msg_app` (`application_id`),
  CONSTRAINT `fk_msg_app`    FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`)      REFERENCES `users`        (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  11. SAVED LISTINGS
-- ─────────────────────────────────────────────
CREATE TABLE `saved_listings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `listing_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_saved` (`user_id`, `listing_id`),
  CONSTRAINT `fk_saved_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  12. PROPERTY IMAGES
-- ─────────────────────────────────────────────
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

-- ─────────────────────────────────────────────
--  13. COMPLAINTS
--     submitter_id + is_anonymous — added in v2_migrations.sql
-- ─────────────────────────────────────────────
CREATE TABLE `complaints` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category`     ENUM('maintenance','noise','safety','management','other') NOT NULL DEFAULT 'other',
  `property_id`  INT UNSIGNED DEFAULT NULL,
  `subject`      VARCHAR(255) NOT NULL,
  `description`  TEXT         NOT NULL,
  `submitter_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL if anonymous',
  `is_anonymous` TINYINT(1)   NOT NULL DEFAULT 1,
  `status`       ENUM('open','under_review','resolved','dismissed') NOT NULL DEFAULT 'open',
  `admin_note`   TEXT         DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`  DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_complaint_status`   (`status`),
  INDEX `idx_complaint_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  14. ANNOUNCEMENTS / CALENDAR EVENTS
-- ─────────────────────────────────────────────
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
  INDEX `idx_ann_date`     (`event_date`),
  CONSTRAINT `fk_ann_property`  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ann_createdby` FOREIGN KEY (`created_by`)  REFERENCES `users`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  15. OWNER APPLICATIONS (review queue)
-- ─────────────────────────────────────────────
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

-- ─────────────────────────────────────────────
--  16. RESIDENT REVIEWS
--     (property rating written by ex-tenants — from migrations.sql v1)
-- ─────────────────────────────────────────────
CREATE TABLE `resident_reviews` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED     NOT NULL,
  `reviewer_id` INT UNSIGNED     NOT NULL,
  `rating`      TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1-5 stars',
  `comment`     TEXT             DEFAULT NULL,
  `is_visible`  TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_review_prop_user` (`property_id`, `reviewer_id`),
  INDEX `idx_rr_property` (`property_id`),
  INDEX `idx_rr_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_rr_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  17. STUDENT RATINGS
--     (aggregate ratings given by landlords — from migrations.sql v1)
-- ─────────────────────────────────────────────
CREATE TABLE `student_ratings` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED     NOT NULL,
  `rater_id`   INT UNSIGNED     NOT NULL COMMENT 'owner or tenant who rates',
  `rating`     TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1-5',
  `comment`    TEXT             DEFAULT NULL,
  `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sr_student_rater` (`student_id`, `rater_id`),
  INDEX `idx_sr_student` (`student_id`),
  INDEX `idx_sr_rater`   (`rater_id`),
  CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_rater`   FOREIGN KEY (`rater_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  18. MOVE-OUT REQUESTS
--     (move-out lifecycle — from v3_migrations.sql)
-- ─────────────────────────────────────────────
CREATE TABLE `move_out_requests` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_tenant_id`  INT UNSIGNED NOT NULL COMMENT 'FK to room_tenants.id',
  `tenant_id`       INT UNSIGNED NOT NULL,
  `room_id`         INT UNSIGNED NOT NULL,
  `owner_id`        INT UNSIGNED NOT NULL COMMENT 'Property owner at time of request',
  `status`          ENUM(
                      'pending',           -- Tenant submitted, owner hasn't acted
                      'owner_accepted',    -- Owner accepted; must review tenant
                      'owner_review_done', -- Owner submitted review; tenant must review property
                      'completed',         -- Both reviews done; moved_out_at set
                      'rejected'           -- Owner rejected the request
                    ) NOT NULL DEFAULT 'pending',
  `tenant_message`  TEXT     DEFAULT NULL COMMENT 'Optional message from tenant',
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_mor_tenant` (`tenant_id`),
  INDEX `idx_mor_owner`  (`owner_id`),
  INDEX `idx_mor_status` (`status`),
  CONSTRAINT `fk_mor_rt`     FOREIGN KEY (`room_tenant_id`) REFERENCES `room_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mor_tenant` FOREIGN KEY (`tenant_id`)      REFERENCES `users`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mor_owner`  FOREIGN KEY (`owner_id`)       REFERENCES `users`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mor_room`   FOREIGN KEY (`room_id`)        REFERENCES `rooms`        (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  19. TENANT REVIEWS  (owner → rates tenant on move-out)
--     (from v3_migrations.sql)
-- ─────────────────────────────────────────────
CREATE TABLE `tenant_reviews` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `move_out_req_id` INT UNSIGNED     NOT NULL,
  `reviewer_id`     INT UNSIGNED     NOT NULL COMMENT 'Owner who wrote this review',
  `tenant_id`       INT UNSIGNED     NOT NULL COMMENT 'Tenant being reviewed',
  `rating`          TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1-5 stars',
  `cleanliness`     TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 sub-rating',
  `behaviour`       TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 sub-rating',
  `punctuality`     TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 sub-rating',
  `comment`         TEXT             DEFAULT NULL,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tr_req`      (`move_out_req_id`) COMMENT 'One review per move-out request',
  INDEX `idx_tr_tenant`   (`tenant_id`),
  INDEX `idx_tr_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_tr_req`      FOREIGN KEY (`move_out_req_id`) REFERENCES `move_out_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tr_reviewer` FOREIGN KEY (`reviewer_id`)     REFERENCES `users`             (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tr_tenant`   FOREIGN KEY (`tenant_id`)       REFERENCES `users`             (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_tr_rating`  CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
--  20. PROPERTY REVIEWS  (tenant → rates property on move-out)
--     (from v3_migrations.sql)
-- ─────────────────────────────────────────────
CREATE TABLE `property_reviews` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `move_out_req_id` INT UNSIGNED     NOT NULL,
  `reviewer_id`     INT UNSIGNED     NOT NULL COMMENT 'Tenant who wrote this review',
  `property_id`     INT UNSIGNED     NOT NULL,
  `rating`          TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1-5 stars',
  `cleanliness`     TINYINT UNSIGNED DEFAULT NULL,
  `safety`          TINYINT UNSIGNED DEFAULT NULL,
  `value_for_money` TINYINT UNSIGNED DEFAULT NULL,
  `comment`         TEXT             DEFAULT NULL,
  `is_public`       TINYINT(1)       NOT NULL DEFAULT 1 COMMENT 'Public-facing reviews',
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pr_req`       (`move_out_req_id`) COMMENT 'One review per move-out',
  INDEX `idx_pr_property`  (`property_id`),
  INDEX `idx_pr_reviewer`  (`reviewer_id`),
  CONSTRAINT `fk_pr_req`      FOREIGN KEY (`move_out_req_id`) REFERENCES `move_out_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_reviewer` FOREIGN KEY (`reviewer_id`)     REFERENCES `users`             (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_property` FOREIGN KEY (`property_id`)     REFERENCES `properties`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_pr_rating`  CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
--  SEED DATA
-- =============================================

-- ── Allowed email domains ───────────────────
INSERT INTO `allowed_domains` (`domain`) VALUES
  ('uiu.ac.bd'),
  ('bscse.uiu.ac.bd'),
  ('student.uiu.ac.bd'),
  ('gmail.com');

-- ── Amenities (SVG icon slugs) ──────────────
INSERT INTO `amenities` (`slug`, `label`, `icon`, `sort_order`) VALUES
  ('wifi',          'High-Speed WiFi',    'wifi',             1),
  ('ac',            'Air Conditioning',   'wind',             2),
  ('attached_bath', 'Attached Bathroom',  'shower-head',      3),
  ('shared_bath',   'Shared Bathroom',    'bath',             4),
  ('furnished',     'Fully Furnished',    'sofa',             5),
  ('balcony',       'Private Balcony',    'sun',              6),
  ('parking',       'Parking Space',      'car',              7),
  ('laundry',       'Laundry Access',     'washing-machine',  8),
  ('security',      '24/7 Security',      'shield',           9),
  ('cctv',          'CCTV Surveillance',  'camera',           10),
  ('study_room',    'Study Room',         'book-open',        11),
  ('rooftop',       'Rooftop Access',     'building',         12),
  ('generator',     'Backup Generator',   'zap',              13),
  ('lift',          'Elevator / Lift',    'arrow-up',         14);

-- ── Users ────────────────────────────────────
-- All passwords = 'admin123'
-- Hash: $2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am

-- id=1  System Admin
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified_at`) VALUES
(1, 'System Admin', 'admin@uiu.ac.bd',
 '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
 'admin', 1, NOW());

-- id=2  Abdullah Shahriar (Owner)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `is_active`, `email_verified_at`) VALUES
(2, 'Abdullah Shahriar', 'shahriar@gmail.com',
 '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
 'owner', '+8801900000001', 1, NOW());

-- id=3  Shahed Khan (Owner)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `is_active`, `email_verified_at`) VALUES
(3, 'Shahed Khan', 'shahed@gmail.com',
 '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
 'owner', '+8801711111111', 1, NOW());

-- id=4  Ritu Datta (Tenant / House Manager)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `is_active`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`) VALUES
(4, 'Ritu Datta', 'ritu@bscse.uiu.ac.bd',
 '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
 'tenant', '+8801722222222', 1, NOW(), '011221050', 'CSE', 4, 'female');

-- id=5  Tahsin Faiyaz (Student)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `phone`, `is_active`, `email_verified_at`, `student_id`, `department`, `year_of_study`, `gender`) VALUES
(5, 'Tahsin Faiyaz', 'tahsin@bscse.uiu.ac.bd',
 '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
 'student', '+8801733333333', 1, NOW(), '011221001', 'CSE', 3, 'male');

-- ── Properties ──────────────────────────────
-- Shahed Khan (id=3) — Sayeed Nagar / Satarkul
INSERT INTO `properties` (`id`, `owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`, `is_active`) VALUES
(1, 3, 'Nikunja Villa',   'Sayeed Nagar, Badda, Dhaka-1212',  23.7991, 90.4505, 'A premium gated residence in the heart of Sayeed Nagar. Features modern amenities and 24/7 security for a safe student life.', 1),
(2, 3, 'Mukul Villa',     'Sayeed Nagar, Badda, Dhaka-1212',  23.7988, 90.4508, 'Well-maintained family hostel with large rooms and a peaceful environment. Close to public transport and daily essentials.', 1),
(3, 3, 'Rupali Kuthir',   'Sayeed Nagar, Badda, Dhaka-1212',  23.7985, 90.4512, 'Compact and budget-friendly. Ideal for students who prefer a quiet, focused study environment.', 1),
(4, 3, 'Nilachol Nibash', 'Satarkul Road, Badda, Dhaka-1212', 23.7960, 90.4550, 'Spacious 6-storey building with rooftop access and CCTV coverage throughout the premises.', 1);

-- Abdullah Shahriar (id=2) — Basundhara R/A
INSERT INTO `properties` (`id`, `owner_id`, `name`, `address`, `location_lat`, `location_lng`, `description`, `is_active`) VALUES
(5, 2, 'Tasin Villa',        'Block-D, Basundhara R/A, Dhaka-1229', 23.8120, 90.4260, 'Premium villa-style residence in Basundhara. Fully gated with generator backup, CCTV, and covered parking.', 1),
(6, 2, 'Campus Edge Hostel', 'Block-C, Basundhara R/A, Dhaka-1229', 23.8115, 90.4255, 'The go-to hostel for UIU students. Located minutes from campus with shared study lounges and fast WiFi.', 1),
(7, 2, 'Simanto Kuthir',     'Block-E, Basundhara R/A, Dhaka-1229', 23.8125, 90.4270, 'Affordable and clean. Ideal for first-year students seeking a secure, friendly hostel environment.', 1);

-- ── Rooms ────────────────────────────────────
-- Nikunja Villa (property 1)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(1, '101', 2, 0, 8000.00, '["wifi","ac","attached_bath","furnished"]',           'Spacious double room, fully AC with attached bath.'),
(1, '102', 1, 0, 6500.00, '["wifi","ac","shared_bath"]',                         'Cozy single room with AC and shared bath.');

-- Mukul Villa (property 2) — Ritu Datta lives in room A1
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(2, 'A1', 2, 1, 7500.00, '["wifi","ac","attached_bath","furnished"]',             'Comfortable double room. Ritu Datta currently resides here.'),
(2, 'A2', 1, 0, 5500.00, '["wifi","shared_bath"]',                               'Single room, quiet corner of the building.');

-- Rupali Kuthir (property 3)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(3, 'G1', 1, 0, 5000.00, '["wifi","shared_bath"]',                               'Budget single near entrance.'),
(3, 'G2', 2, 0, 7000.00, '["wifi","ac","shared_bath"]',                          'Double room with AC.');

-- Nilachol Nibash (property 4)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(4, 'B1', 2, 0, 9000.00, '["wifi","ac","attached_bath","furnished","cctv","security"]', 'Premium double room with full security amenities.'),
(4, 'B2', 3, 0, 7000.00, '["wifi","shared_bath","cctv"]',                               'Triple-sharing room with CCTV.');

-- Tasin Villa (property 5)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(5, 'T1', 2, 0, 10000.00, '["wifi","ac","attached_bath","furnished","parking","generator"]', 'Premium double room with all facilities.'),
(5, 'T2', 1, 0,  7500.00, '["wifi","ac","shared_bath","furnished"]',                         'Single room with AC and shared bath.');

-- Campus Edge Hostel (property 6)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(6, 'C1', 1, 0, 5500.00, '["wifi","shared_bath","study_room"]',                  'Single room, study lounge access.'),
(6, 'C2', 2, 0, 8500.00, '["wifi","ac","attached_bath","study_room"]',           'Double room with AC and attached bath.');

-- Simanto Kuthir (property 7)
INSERT INTO `rooms` (`property_id`, `room_number`, `capacity`, `current_occupancy`, `rent_amount`, `amenities_json`, `description`) VALUES
(7, 'S1', 1, 0, 4500.00, '["wifi","shared_bath"]',                               'Affordable single room.'),
(7, 'S2', 2, 0, 6000.00, '["wifi","ac","shared_bath"]',                          'Double room with AC.');

-- ── Room Tenants ─────────────────────────────
-- Ritu Datta (id=4) lives in Mukul Villa room A1 (room_id=3)
INSERT INTO `room_tenants` (`room_id`, `user_id`, `moved_in_at`) VALUES (3, 4, NOW());

-- ── Listings ─────────────────────────────────
INSERT INTO `listings` (`room_id`, `created_by`, `listing_type`, `title`, `description`, `status`, `published_at`) VALUES
-- Nikunja Villa
(1,  3, 'owner_direct', 'Double Room at Nikunja Villa — AC & Attached Bath',     'Fully furnished AC double room in prime Sayeed Nagar location. Perfect for two students.',          'published', NOW()),
(2,  3, 'owner_direct', 'Single Room at Nikunja Villa',                           'Clean single room with WiFi and AC. Ideal for focused students.',                                    'published', NOW()),
-- Mukul Villa (A1 is occupied — only A2 listed)
(4,  3, 'owner_direct', 'Single Room Available at Mukul Villa',                   'Quiet single room in well-managed Mukul Villa.',                                                     'published', NOW()),
-- Rupali Kuthir
(5,  3, 'owner_direct', 'Budget Single Room — Rupali Kuthir',                     'Affordable single room near public transport.',                                                      'published', NOW()),
(6,  3, 'owner_direct', 'Double Room at Rupali Kuthir — AC',                      'Comfortable double room with AC and shared bath.',                                                   'published', NOW()),
-- Nilachol Nibash
(7,  3, 'owner_direct', 'Premium Double Room at Nilachol Nibash',                 'High-security double room with full CCTV coverage.',                                                'published', NOW()),
(8,  3, 'owner_direct', 'Triple Sharing Room — Nilachol Nibash',                  'Great for groups of 3. CCTV covered, rooftop access.',                                              'published', NOW()),
-- Tasin Villa
(9,  2, 'owner_direct', 'Premium Double Room — Tasin Villa, Basundhara',          'Top-tier room with all amenities including parking and generator.',                                  'published', NOW()),
(10, 2, 'owner_direct', 'Single Room at Tasin Villa',                             'AC single room in a premium gated community.',                                                       'published', NOW()),
-- Campus Edge Hostel
(11, 2, 'owner_direct', 'Single Room at Campus Edge Hostel',                      'Minutes from UIU campus. Study lounge access included.',                                            'published', NOW()),
(12, 2, 'owner_direct', 'Double Room at Campus Edge — AC & Attached Bath',        'Well-ventilated double with attached bath and study room.',                                          'published', NOW()),
-- Simanto Kuthir
(13, 2, 'owner_direct', 'Budget Single — Simanto Kuthir, Basundhara',             'Great value single room for first-year students.',                                                   'published', NOW()),
(14, 2, 'owner_direct', 'Double Room at Simanto Kuthir',                           'AC double room in a clean, friendly hostel.',                                                       'published', NOW());

-- ── Listing Requirements ─────────────────────
INSERT INTO `listing_requirements` (`listing_id`, `preferred_gender`, `smoking_allowed`, `is_mandatory`) VALUES
(1,  'any', 0, 0),
(2,  'any', 0, 0),
(3,  'any', 0, 0),
(4,  'any', 0, 0),
(5,  'any', 0, 0),
(6,  'any', 0, 0),
(7,  'any', 0, 0),
(8,  'any', 0, 0),
(9,  'any', 0, 0),
(10, 'any', 0, 0),
(11, 'any', 0, 0),
(12, 'any', 0, 0),
(13, 'any', 0, 0);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
--  AFTER IMPORT — Login Credentials
--  (password for all accounts: admin123)
--
--  admin@uiu.ac.bd          → System Admin
--  shahriar@gmail.com       → Abdullah Shahriar (Owner)
--  shahed@gmail.com         → Shahed Khan (Owner)
--  ritu@bscse.uiu.ac.bd    → Ritu Datta (Tenant)
--  tahsin@bscse.uiu.ac.bd  → Tahsin Faiyaz (Student)
-- =============================================