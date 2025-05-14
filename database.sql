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