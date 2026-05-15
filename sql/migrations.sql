-- =============================================
-- UIU Nest — Migration File
-- Run these ALTER/CREATE statements against your
-- existing uiu_nest database to add new features.
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 1. Remove video_path from application_documents
--    (video requirement removed per new spec)
-- ---------------------------------------------
ALTER TABLE `application_documents`
    DROP COLUMN IF EXISTS `video_path`;

-- ---------------------------------------------
-- 2. Add house_manager_id to properties
--    (so applications route to manager too)
--    Uses a stored procedure for MariaDB compatibility
-- ---------------------------------------------
DROP PROCEDURE IF EXISTS `add_house_manager_col`;
DELIMITER //
CREATE PROCEDURE `add_house_manager_col`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'properties'
          AND COLUMN_NAME  = 'house_manager_id'
    ) THEN
        ALTER TABLE `properties`
            ADD COLUMN `house_manager_id` INT UNSIGNED DEFAULT NULL AFTER `owner_id`,
            ADD INDEX  `idx_prop_manager` (`house_manager_id`),
            ADD CONSTRAINT `fk_prop_manager`
                FOREIGN KEY (`house_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
    END IF;
END //
DELIMITER ;
CALL `add_house_manager_col`();
DROP PROCEDURE IF EXISTS `add_house_manager_col`;

-- ---------------------------------------------
-- 3. RESIDENT REVIEWS
--    Students who have stayed in a property can leave
--    a rating+comment visible on the property detail page.
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `resident_reviews` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED    NOT NULL,
  `reviewer_id` INT UNSIGNED    NOT NULL,
  `rating`      TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1-5 stars',
  `comment`     TEXT            DEFAULT NULL,
  `is_visible`  TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_review_prop_user` (`property_id`, `reviewer_id`),
  INDEX `idx_rr_property` (`property_id`),
  INDEX `idx_rr_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_rr_property` FOREIGN KEY (`property_id`)
    REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_reviewer` FOREIGN KEY (`reviewer_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 4. STUDENT RATINGS  (aggregate ratings given by landlords)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `student_ratings` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `student_id`  INT UNSIGNED    NOT NULL,
  `rater_id`    INT UNSIGNED    NOT NULL COMMENT 'owner or tenant who rates',
  `rating`      TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '1-5',
  `comment`     TEXT            DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sr_student_rater` (`student_id`, `rater_id`),
  INDEX `idx_sr_student` (`student_id`),
  INDEX `idx_sr_rater` (`rater_id`),
  CONSTRAINT `fk_sr_student` FOREIGN KEY (`student_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_rater` FOREIGN KEY (`rater_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 5. ANNOUNCEMENTS / CALENDAR EVENTS
--    Owners set rent-due / announcement dates;
--    tenants of that property see them.
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED    NOT NULL,
  `created_by`  INT UNSIGNED    NOT NULL,
  `event_date`  DATE            NOT NULL,
  `type`        ENUM('rent_due','announcement','maintenance','other')
                                NOT NULL DEFAULT 'announcement',
  `title`       VARCHAR(255)    NOT NULL,
  `description` TEXT            DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ann_property` (`property_id`),
  INDEX `idx_ann_date` (`event_date`),
  CONSTRAINT `fk_ann_property` FOREIGN KEY (`property_id`)
    REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ann_creator` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 6. ANONYMOUS COMPLAINTS
--    Fully anonymous — no submitter identity stored.
--    Only admin can see & manage.
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `category`    ENUM('maintenance','noise','safety','management','other')
                                NOT NULL DEFAULT 'other',
  `property_id` INT UNSIGNED    DEFAULT NULL COMMENT 'optional target property',
  `subject`     VARCHAR(255)    NOT NULL,
  `description` TEXT            NOT NULL,
  `status`      ENUM('open','under_review','resolved','dismissed')
                                NOT NULL DEFAULT 'open',
  `admin_note`  TEXT            DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_complaint_status` (`status`),
  INDEX `idx_complaint_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
