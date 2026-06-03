-- =============================================
-- UIU Nest — V3 Migration
-- Task 1: Hierarchy Fix + Move-Out Lifecycle Tables
-- Run AFTER v2_migrations.sql in phpMyAdmin
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── STEP 1: Hierarchy Fix ────────────────────────────────────────────────────

-- Rename ID=1 from "Abdullah Shahriar" to "System Admin" (pure admin account)
UPDATE `users`
   SET `full_name` = 'System Admin',
       `email`     = 'admin@uiu.ac.bd',
       `role`      = 'admin'
 WHERE `id` = 1;

-- Ensure "Abdullah Shahriar" exists as a dedicated owner account.
-- If the email already exists, just update the role; otherwise INSERT.
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `phone`, `email_verified_at`, `is_active`)
SELECT 'Abdullah Shahriar', 'shahriar@gmail.com',
       '$2y$12$3nZyoZa02a1dnOIfNH20COhDokzejvKiw0IFgFtDXxvftuC4rK2am',
       'owner', '+8801900000001', NOW(), 1
WHERE NOT EXISTS (
    SELECT 1 FROM `users` WHERE `email` = 'shahriar@gmail.com'
);

-- If the row already existed, ensure the role is owner
UPDATE `users`
   SET `role`      = 'owner',
       `full_name` = 'Abdullah Shahriar'
 WHERE `email` = 'shahriar@gmail.com';

-- ─── STEP 2: New table — cover_photo_position on properties ───────────────────
-- Stores the CSS object-position for draggable cover photo adjustment
ALTER TABLE `properties`
    ADD COLUMN IF NOT EXISTS `cover_photo_position` VARCHAR(30) NOT NULL DEFAULT '50% 50%'
        COMMENT 'CSS object-position value, e.g. "30% 60%"';

-- ─── STEP 3: New table — move_out_requests ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `move_out_requests` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_tenant_id` INT UNSIGNED NOT NULL  COMMENT 'FK to room_tenants.id',
    `tenant_id`     INT UNSIGNED NOT NULL,
    `room_id`       INT UNSIGNED NOT NULL,
    `owner_id`      INT UNSIGNED NOT NULL   COMMENT 'Property owner at time of request',
    `status`        ENUM(
                        'pending',          -- Tenant submitted, owner hasn't acted
                        'owner_accepted',   -- Owner accepted; owner must review tenant
                        'owner_review_done',-- Owner submitted tenant_review; tenant must review property
                        'completed',        -- Both reviews done; moved_out_at set
                        'rejected'          -- Owner rejected the request
                    ) NOT NULL DEFAULT 'pending',
    `tenant_message` TEXT DEFAULT NULL      COMMENT 'Optional message from tenant',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mor_tenant`  (`tenant_id`),
    INDEX `idx_mor_owner`   (`owner_id`),
    INDEX `idx_mor_status`  (`status`),
    CONSTRAINT `fk_mor_rt`     FOREIGN KEY (`room_tenant_id`) REFERENCES `room_tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mor_tenant` FOREIGN KEY (`tenant_id`)      REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mor_owner`  FOREIGN KEY (`owner_id`)       REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mor_room`   FOREIGN KEY (`room_id`)        REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── STEP 4: New table — tenant_reviews (owner → rates tenant) ───────────────
CREATE TABLE IF NOT EXISTS `tenant_reviews` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `move_out_req_id` INT UNSIGNED NOT NULL,
    `reviewer_id`     INT UNSIGNED NOT NULL  COMMENT 'Owner who wrote this review',
    `tenant_id`       INT UNSIGNED NOT NULL  COMMENT 'Tenant being reviewed',
    `rating`          TINYINT UNSIGNED NOT NULL DEFAULT 5
                          COMMENT '1–5 stars',
    `cleanliness`     TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 sub-rating',
    `behaviour`       TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 sub-rating',
    `punctuality`     TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 overall punctuality',
    `comment`         TEXT DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tr_req` (`move_out_req_id`) COMMENT 'One review per move-out request',
    INDEX `idx_tr_tenant`    (`tenant_id`),
    INDEX `idx_tr_reviewer`  (`reviewer_id`),
    CONSTRAINT `fk_tr_req`      FOREIGN KEY (`move_out_req_id`) REFERENCES `move_out_requests` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tr_reviewer` FOREIGN KEY (`reviewer_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tr_tenant`   FOREIGN KEY (`tenant_id`)       REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_tr_rating`  CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── STEP 5: New table — property_reviews (tenant → rates property) ──────────
CREATE TABLE IF NOT EXISTS `property_reviews` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `move_out_req_id` INT UNSIGNED NOT NULL,
    `reviewer_id`     INT UNSIGNED NOT NULL  COMMENT 'Tenant who wrote this review',
    `property_id`     INT UNSIGNED NOT NULL,
    `rating`          TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1–5 stars',
    `cleanliness`     TINYINT UNSIGNED DEFAULT NULL,
    `safety`          TINYINT UNSIGNED DEFAULT NULL,
    `value_for_money` TINYINT UNSIGNED DEFAULT NULL,
    `comment`         TEXT DEFAULT NULL,
    `is_public`       TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Public-facing reviews',
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pr_req` (`move_out_req_id`) COMMENT 'One review per move-out',
    INDEX `idx_pr_property`  (`property_id`),
    INDEX `idx_pr_reviewer`  (`reviewer_id`),
    CONSTRAINT `fk_pr_req`       FOREIGN KEY (`move_out_req_id`) REFERENCES `move_out_requests` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pr_reviewer`  FOREIGN KEY (`reviewer_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pr_property`  FOREIGN KEY (`property_id`)     REFERENCES `properties` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_pr_rating`   CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- After running this migration:
--   - User ID=1 is now "System Admin" (admin role)
--   - "Abdullah Shahriar" has his own owner row
--   - move_out_requests, tenant_reviews, property_reviews tables are created
--   - properties.cover_photo_position column added
-- =============================================
