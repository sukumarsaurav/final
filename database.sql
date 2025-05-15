-- Create membership plans table
CREATE TABLE `membership_plans` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `max_team_members` int(11) NOT NULL,
    `price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `billing_cycle` enum('monthly','quarterly','annually') NOT NULL DEFAULT 'monthly',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `membership_plans` (`name`, `max_team_members`, `price`, `billing_cycle`) VALUES
('Bronze', 5, 29.99, 'monthly'),
('Bronze', 5, 79.99, 'quarterly'),
('Bronze', 5, 299.99, 'annually'),
('Silver', 10, 49.99, 'monthly'),
('Silver', 10, 139.99, 'quarterly'),
('Silver', 10, 499.99, 'annually'),
('Gold', 20, 99.99, 'monthly'),
('Gold', 20, 279.99, 'quarterly'),
('Gold', 20, 999.99, 'annually');
-- Create organizations table
CREATE TABLE `organizations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create users table (modified from existing schema)
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `first_name` varchar(100) NOT NULL,
    `last_name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL COMMENT 'Store only hashed passwords',
    `user_type` enum('applicant','consultant','admin','member','custom') NOT NULL,
    `email_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Set to 1 after OTP verification',
    `email_verification_token` varchar(100) DEFAULT NULL,
    `email_verification_expires` datetime DEFAULT NULL,
    `status` enum('active','suspended') NOT NULL DEFAULT 'active',
    `password_reset_token` varchar(100) DEFAULT NULL,
    `password_reset_expires` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `deleted_at` datetime DEFAULT NULL,
    `google_id` VARCHAR(255) NULL,
    `auth_provider` ENUM('local', 'google') DEFAULT 'local',
    `profile_picture` VARCHAR(255) NULL,
    `organization_id` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `google_id` (`google_id`),
    KEY `idx_users_user_type_status` (`user_type`, `status`, `deleted_at`),
    KEY `idx_users_email_verified` (`email_verified`),
    KEY `idx_users_organization` (`organization_id`),
    CONSTRAINT `users_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create consultants table (extends users)
CREATE TABLE `consultants` (
    `user_id` int(11) NOT NULL,
    `membership_plan_id` int(11) NOT NULL,
    `company_name` varchar(100) DEFAULT NULL,
    `team_members_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`user_id`),
    KEY `idx_consultants_membership` (`membership_plan_id`),
    CONSTRAINT `consultants_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `consultants_membership_plan_id_fk` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create applicants table (extends users)
CREATE TABLE `applicants` (
    `user_id` int(11) NOT NULL,
    `passport_number` varchar(50) DEFAULT NULL,
    `nationality` varchar(100) DEFAULT NULL,
    `date_of_birth` date DEFAULT NULL,
    `place_of_birth` varchar(100) DEFAULT NULL,
    `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
    `current_country` varchar(100) DEFAULT NULL,
    `current_visa_status` varchar(100) DEFAULT NULL,
    `visa_expiry_date` date DEFAULT NULL,
    `target_country` varchar(100) DEFAULT NULL COMMENT 'Country interested in immigrating to',
    `immigration_purpose` enum('study','work','business','family','refugee','permanent_residence') DEFAULT NULL,
    `education_level` enum('high_school','bachelors','masters','phd','other') DEFAULT NULL,
    `occupation` varchar(100) DEFAULT NULL,
    `english_proficiency` enum('basic','intermediate','advanced','native','none') DEFAULT NULL,
    `has_previous_refusals` tinyint(1) DEFAULT 0 COMMENT 'Previous visa/immigration refusals',
    `refusal_details` text DEFAULT NULL,
    `has_family_in_target_country` tinyint(1) DEFAULT 0,
    `family_relation_details` text DEFAULT NULL,
    `net_worth` decimal(12,2) DEFAULT NULL COMMENT 'Financial information',
    `documents_folder_url` varchar(255) DEFAULT NULL COMMENT 'Cloud folder with supporting documents',
    `application_stage` enum('inquiry','assessment','document_collection','application_submitted','processing','decision_received','post_approval') DEFAULT 'inquiry',
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`user_id`),
    KEY `idx_applicants_nationality` (`nationality`),
    KEY `idx_applicants_target_country` (`target_country`),
    KEY `idx_applicants_application_stage` (`application_stage`),
    KEY `idx_applicants_immigration_purpose` (`immigration_purpose`),
    CONSTRAINT `applicants_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create team_members table for tracking team relationships
CREATE TABLE `team_members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `consultant_id` int(11) NOT NULL,
    `member_user_id` int(11) NOT NULL,
    `member_type` varchar(50) NOT NULL COMMENT 'consultant or custom',
    `invitation_status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `accepted_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `consultant_member_unique` (`consultant_id`, `member_user_id`),
    KEY `idx_team_members_consultant` (`consultant_id`),
    KEY `idx_team_members_status` (`invitation_status`),
    CONSTRAINT `team_members_consultant_id_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `team_members_member_user_id_fk` FOREIGN KEY (`member_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create oauth_tokens table (from existing schema)
CREATE TABLE `oauth_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `provider` varchar(50) NOT NULL,
    `provider_user_id` varchar(255) NOT NULL,
    `access_token` text NOT NULL,
    `refresh_token` text DEFAULT NULL,
    `token_expires` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `provider_user_unique` (`provider`, `provider_user_id`),
    KEY `idx_oauth_tokens_user` (`user_id`),
    CONSTRAINT `oauth_tokens_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert membership plans
INSERT INTO `membership_plans` (`name`, `max_team_members`) VALUES
('Bronze', 5),
('Silver', 10),
('Gold', 20);

-- Create trigger to verify team member limits
DELIMITER //
CREATE TRIGGER `check_team_member_limit` BEFORE INSERT ON `team_members`
FOR EACH ROW
BEGIN
    DECLARE current_count INT;
    DECLARE max_allowed INT;
    
    -- Get current count of team members for this consultant
    SELECT `team_members_count` INTO current_count
    FROM `consultants`
    WHERE `user_id` = NEW.`consultant_id`;
    
    -- Get max allowed team members based on membership plan
    SELECT mp.`max_team_members` INTO max_allowed
    FROM `consultants` c
    JOIN `membership_plans` mp ON c.`membership_plan_id` = mp.`id`
    WHERE c.`user_id` = NEW.`consultant_id`;
    
    -- Check if adding one more member would exceed the limit
    IF current_count >= max_allowed THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Cannot add more team members. Membership plan limit reached.';
    END IF;
END //
DELIMITER ;

-- Create trigger to update team_members_count when a member is added
DELIMITER //
CREATE TRIGGER `update_team_count_after_insert` AFTER INSERT ON `team_members`
FOR EACH ROW
BEGIN
    UPDATE `consultants`
    SET `team_members_count` = `team_members_count` + 1
    WHERE `user_id` = NEW.`consultant_id`;
END //
DELIMITER ;

-- Create trigger to decrease team_members_count when a member is removed
DELIMITER //
CREATE TRIGGER `update_team_count_after_delete` AFTER DELETE ON `team_members`
FOR EACH ROW
BEGIN
    UPDATE `consultants`
    SET `team_members_count` = `team_members_count` - 1
    WHERE `user_id` = OLD.`consultant_id`;
END //
DELIMITER ;

-- Create procedure to upgrade membership plan
DELIMITER //
CREATE PROCEDURE `upgrade_membership_plan`(
    IN p_user_id INT,
    IN p_new_plan_id INT
)
BEGIN
    UPDATE `consultants`
    SET `membership_plan_id` = p_new_plan_id
    WHERE `user_id` = p_user_id;
END //
DELIMITER ;

-- Create view to list all consultants with their team info
CREATE VIEW `consultant_teams` AS
SELECT 
    u.`id` AS consultant_id,
    u.`first_name`,
    u.`last_name`,
    u.`email`,
    c.`company_name`,
    mp.`name` AS membership_plan,
    mp.`max_team_members`,
    c.`team_members_count`,
    (mp.`max_team_members` - c.`team_members_count`) AS available_slots
FROM 
    `users` u
JOIN 
    `consultants` c ON u.`id` = c.`user_id`
JOIN 
    `membership_plans` mp ON c.`membership_plan_id` = mp.`id`
WHERE
    u.`deleted_at` IS NULL AND u.`status` = 'active';

-- Add price and billing_cycle to membership_plans table
ALTER TABLE `membership_plans` 
ADD COLUMN `price` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `max_team_members`,
ADD COLUMN `billing_cycle` enum('monthly','quarterly','annually') NOT NULL DEFAULT 'monthly' AFTER `price`;

-- Create payment_methods table
CREATE TABLE `payment_methods` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `method_type` enum('credit_card','paypal','bank_transfer') NOT NULL,
    `provider` varchar(50) NOT NULL,
    `account_number` varchar(255) DEFAULT NULL COMMENT 'Last 4 digits for credit cards',
    `expiry_date` varchar(10) DEFAULT NULL COMMENT 'MM/YY format for credit cards',
    `token` varchar(255) DEFAULT NULL COMMENT 'Payment provider token',
    `is_default` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_payment_methods_user` (`user_id`),
    CONSTRAINT `payment_methods_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create subscriptions table
CREATE TABLE `subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `membership_plan_id` int(11) NOT NULL,
    `payment_method_id` int(11) DEFAULT NULL,
    `status` enum('active','canceled','expired','pending') NOT NULL DEFAULT 'pending',
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_subscriptions_user` (`user_id`),
    KEY `idx_subscriptions_plan` (`membership_plan_id`),
    KEY `idx_subscriptions_payment_method` (`payment_method_id`),
    KEY `idx_subscriptions_status` (`status`),
    CONSTRAINT `subscriptions_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `subscriptions_membership_plan_id_fk` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`),
    CONSTRAINT `subscriptions_payment_method_id_fk` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create payments table for tracking payment history
CREATE TABLE `payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `subscription_id` int(11) DEFAULT NULL,
    `payment_method_id` int(11) DEFAULT NULL,
    `amount` decimal(10,2) NOT NULL,
    `currency` varchar(3) NOT NULL DEFAULT 'USD',
    `status` enum('pending','completed','failed','refunded') NOT NULL,
    `transaction_id` varchar(255) DEFAULT NULL COMMENT 'External payment processor transaction ID',
    `payment_date` datetime NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_payments_user` (`user_id`),
    KEY `idx_payments_subscription` (`subscription_id`),
    KEY `idx_payments_method` (`payment_method_id`),
    KEY `idx_payments_status` (`status`),
    CONSTRAINT `payments_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `payments_subscription_id_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `payments_payment_method_id_fk` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update membership_plans with price information
UPDATE `membership_plans` SET `price` = 49.99 WHERE `name` = 'Bronze';
UPDATE `membership_plans` SET `price` = 99.99 WHERE `name` = 'Silver';
UPDATE `membership_plans` SET `price` = 199.99 WHERE `name` = 'Gold';

-- Add a new relationship table between applicants and consultants
CREATE TABLE `applicant_consultant_relationships` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `applicant_id` INT NOT NULL,
    `consultant_id` INT NOT NULL,
    `relationship_type` ENUM('primary', 'secondary', 'referred') NOT NULL DEFAULT 'primary',
    `status` ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    `notes` TEXT,
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`applicant_id`, `consultant_id`),
    KEY `idx_applicant_consultant_organization` (`organization_id`)
);

-- Add a notification preferences table for booking-related notifications
CREATE TABLE `notification_preferences` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `booking_confirmation` BOOLEAN DEFAULT TRUE,
    `booking_reminder` BOOLEAN DEFAULT TRUE,
    `reminder_hours_before` INT DEFAULT 24,
    `booking_cancellation` BOOLEAN DEFAULT TRUE,
    `booking_rescheduled` BOOLEAN DEFAULT TRUE,
    `payment_confirmation` BOOLEAN DEFAULT TRUE,
    `email_notifications` BOOLEAN DEFAULT TRUE,
    `sms_notifications` BOOLEAN DEFAULT FALSE,
    `push_notifications` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`user_id`)
);

-- Add a view to show consultants with their booking statistics
CREATE OR REPLACE VIEW consultant_booking_stats_view AS
SELECT 
    c.user_id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    c.company_name,
    u.organization_id,
    o.name AS organization_name,
    COUNT(DISTINCT b.id) AS total_bookings,
    SUM(CASE WHEN bs.name = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
    SUM(CASE WHEN bs.name IN ('cancelled_by_user', 'cancelled_by_admin', 'cancelled_by_consultant') THEN 1 ELSE 0 END) AS cancelled_bookings,
    SUM(CASE WHEN bs.name = 'pending' THEN 1 ELSE 0 END) AS pending_bookings,
    SUM(CASE WHEN bs.name = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
    ROUND(AVG(bf.rating), 1) AS average_rating,
    COUNT(DISTINCT bf.id) AS total_ratings
FROM 
    consultants c
JOIN 
    users u ON c.user_id = u.id
JOIN 
    organizations o ON u.organization_id = o.id
LEFT JOIN 
    bookings b ON c.user_id = b.consultant_id AND b.deleted_at IS NULL
LEFT JOIN 
    booking_statuses bs ON b.status_id = bs.id
LEFT JOIN 
    booking_feedback bf ON b.id = bf.booking_id
WHERE 
    u.status = 'active' 
    AND u.deleted_at IS NULL
GROUP BY 
    c.user_id, consultant_name, c.company_name, u.organization_id, o.name;

-- Add consultant profile details table
CREATE TABLE `consultant_profiles` (
    `consultant_id` INT PRIMARY KEY,
    `bio` TEXT,
    `specializations` TEXT,
    `years_experience` INT DEFAULT 0,
    `education` TEXT,
    `certifications` TEXT,
    `languages` TEXT,
    `profile_image` VARCHAR(255),
    `banner_image` VARCHAR(255),
    `website` VARCHAR(255),
    `social_linkedin` VARCHAR(255),
    `social_twitter` VARCHAR(255),
    `social_facebook` VARCHAR(255),
    `is_featured` BOOLEAN DEFAULT FALSE,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `display_order` INT DEFAULT 0,
    `seo_title` VARCHAR(255),
    `seo_description` TEXT,
    `seo_keywords` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE
);
-- Add verified_by column to consultant_profiles to track who verified the consultant
ALTER TABLE `consultant_profiles` 
ADD COLUMN `verified_by` INT NULL AFTER `is_verified`,
ADD COLUMN `verified_at` DATETIME NULL AFTER `verified_by`,
ADD CONSTRAINT `verified_by_fk` FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`);

-- Create a table to track verification documents
CREATE TABLE IF NOT EXISTS `consultant_verifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `consultant_id` INT NOT NULL,
  `document_type` VARCHAR(50) NOT NULL, -- e.g., 'business_license', 'id_proof', 'certification'
  `document_path` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified` BOOLEAN DEFAULT FALSE,
  `verified_by` INT NULL,
  `verified_at` DATETIME NULL,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_consultant_id` (`consultant_id`),
  CONSTRAINT `consultant_verifications_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `verification_verified_by_fk` FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 
-- Add consultant availability settings table
CREATE TABLE `consultant_availability_settings` (
    `consultant_id` INT PRIMARY KEY,
    `advance_booking_days` INT DEFAULT 90,
    `min_notice_hours` INT DEFAULT 24,
    `max_daily_bookings` INT DEFAULT 10,
    `default_appointment_duration` INT DEFAULT 60,
    `buffer_between_appointments` INT DEFAULT 15,
    `auto_confirm_bookings` BOOLEAN DEFAULT FALSE,
    `allow_instant_bookings` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE
);

-- Add view for consultant public profiles
CREATE OR REPLACE VIEW consultant_public_profiles_view AS
SELECT 
    u.id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    c.company_name,
    cp.bio,
    cp.specializations,
    cp.years_experience,
    cp.certifications,
    cp.languages,
    cp.profile_image,
    cp.banner_image,
    cp.website,
    u.organization_id,
    o.name AS organization_name,
    COUNT(DISTINCT vs.visa_service_id) AS services_count,
    COUNT(DISTINCT b.id) AS bookings_count,
    ROUND(AVG(bf.rating), 1) AS average_rating,
    COUNT(DISTINCT bf.id) AS reviews_count,
    cp.is_featured,
    cp.display_order
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
JOIN 
    organizations o ON u.organization_id = o.id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN 
    visa_services vs ON u.id = vs.consultant_id AND vs.is_active = 1
LEFT JOIN 
    bookings b ON u.id = b.consultant_id AND b.deleted_at IS NULL
LEFT JOIN 
    booking_feedback bf ON b.id = bf.booking_id
WHERE 
    u.status = 'active' 
    AND u.deleted_at IS NULL
    AND u.user_type = 'consultant'
GROUP BY 
    u.id, consultant_name, c.company_name, cp.bio, cp.specializations, 
    cp.years_experience, cp.certifications, cp.languages, cp.profile_image, 
    cp.banner_image, cp.website, u.organization_id, o.name, 
    cp.is_featured, cp.display_order
ORDER BY 
    cp.is_featured DESC, cp.display_order ASC, average_rating DESC;