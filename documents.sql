-- Document categories for organization
CREATE TABLE `document_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `organization_id` int(11) NULL,
  `is_global` BOOLEAN DEFAULT FALSE,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_document_categories_organization` (`organization_id`),
  CONSTRAINT `document_categories_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert common document categories
INSERT INTO `document_categories` (`name`, `description`) VALUES
('Identity', 'Identity documents like passport, ID card'),
('Education', 'Educational certificates and transcripts'),
('Employment', 'Employment proof and work history'),
('Financial', 'Bank statements and financial documents'),
('Immigration', 'Previous visas and immigration history'),
('Medical', 'Medical certificates and health records'),
('Supporting', 'Supporting documents like cover letters, photos');

-- Document types master table
CREATE TABLE `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `organization_id` int(11) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_global` BOOLEAN DEFAULT FALSE,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_document_type_name` (`organization_id`, `name`),
  KEY `idx_document_category` (`category_id`),
  KEY `idx_document_types_organization` (`organization_id`),
  CONSTRAINT `document_types_category_id_fk` FOREIGN KEY (`category_id`) REFERENCES `document_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `document_types_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert common document types
INSERT INTO `document_types` (`category_id`, `name`, `description`) VALUES
(1, 'Passport', 'Valid passport with at least 6 months validity'),
(1, 'National ID Card', 'Government-issued national identification card'),
(2, 'Degree Certificate', 'University or college degree certificate'),
(2, 'Transcripts', 'Academic transcripts and mark sheets'),
(3, 'Employment Contract', 'Current employment contract'),
(3, 'Experience Letter', 'Work experience letter from employer'),
(4, 'Bank Statement', 'Bank statement for the last 6 months'),
(4, 'Income Tax Returns', 'Income tax returns for the last 3 years'),
(5, 'Previous Visa', 'Copy of previous visas'),
(6, 'Medical Certificate', 'Medical fitness certificate'),
(7, 'Photographs', 'Passport-sized photographs');

-- Document Templates table
CREATE TABLE `document_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `organization_id` int(11) NOT NULL,
  `consultant_id` int(11) NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_template_name` (`organization_id`, `name`),
  KEY `document_type_id` (`document_type_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_document_templates_organization` (`organization_id`),
  KEY `idx_document_templates_consultant` (`consultant_id`),
  CONSTRAINT `templates_document_type_id_fk` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `templates_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `document_templates_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_templates_consultant_id_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Generated Documents table
CREATE TABLE `generated_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `generated_date` datetime NOT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent_date` datetime DEFAULT NULL,
  `organization_id` int(11) NOT NULL,
  `application_id` int(11) NULL,
  `booking_id` int(11) NULL,
  `consultant_id` int(11) NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_type_id` (`document_type_id`),
  KEY `template_id` (`template_id`),
  KEY `client_id` (`client_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_generated_documents_organization` (`organization_id`),
  KEY `idx_generated_documents_application` (`application_id`),
  KEY `idx_generated_documents_booking` (`booking_id`),
  KEY `idx_generated_documents_consultant` (`consultant_id`),
  CONSTRAINT `generated_document_type_id_fk` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `generated_template_id_fk` FOREIGN KEY (`template_id`) REFERENCES `document_templates` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `generated_client_id_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `generated_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `generated_documents_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `generated_documents_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `generated_documents_booking_id_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `generated_documents_consultant_id_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `template_type` enum('general','welcome','password_reset','booking_confirmation','booking_reminder','booking_cancellation','application_status','document_request','document_approval','document_rejection','marketing','newsletter') NOT NULL DEFAULT 'general',
  `organization_id` int(11) NULL,
  `is_global` BOOLEAN DEFAULT FALSE,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `template_type` (`template_type`),
  KEY `idx_email_templates_organization` (`organization_id`),
  CONSTRAINT `email_templates_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `email_templates_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create email queue table if it doesn't exist
CREATE TABLE IF NOT EXISTS `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) DEFAULT NULL COMMENT 'User ID if recipient exists in system',
  `recipient_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `status` enum('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `scheduled_time` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `organization_id` int(11) NULL,
  `application_id` int(11) NULL,
  `booking_id` int(11) NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `scheduled_time` (`scheduled_time`),
  KEY `idx_email_queue_organization` (`organization_id`),
  KEY `idx_email_queue_application` (`application_id`),
  KEY `idx_email_queue_booking` (`booking_id`),
  CONSTRAINT `email_queue_recipient_id_fk` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_queue_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `email_queue_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_queue_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_queue_booking_id_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create received emails table if it doesn't exist
CREATE TABLE IF NOT EXISTS `received_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT NULL COMMENT 'User ID if sender exists in system',
  `sender_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `received_at` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_by` int(11) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `organization_id` int(11) NULL,
  `application_id` int(11) NULL,
  `booking_id` int(11) NULL,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `read_by` (`read_by`),
  KEY `idx_received_emails_organization` (`organization_id`),
  KEY `idx_received_emails_application` (`application_id`),
  KEY `idx_received_emails_booking` (`booking_id`),
  CONSTRAINT `received_emails_sender_id_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `received_emails_read_by_fk` FOREIGN KEY (`read_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `received_emails_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `received_emails_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `received_emails_booking_id_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create email automation settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `email_automation_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `organization_id` int(11) NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_setting_key` (`organization_id`, `setting_key`),
  KEY `idx_email_automation_settings_organization` (`organization_id`),
  CONSTRAINT `email_automation_settings_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default email automation settings if they don't exist
INSERT IGNORE INTO `email_automation_settings` (`setting_key`, `setting_value`) VALUES
('booking_confirmation_enabled', '1'),
('booking_reminder_enabled', '1'),
('booking_reminder_hours', '24'),
('booking_cancellation_enabled', '1'),
('application_status_enabled', '1'),
('document_request_enabled', '1'),
('document_review_enabled', '1'),
('welcome_email_enabled', '1'),
('password_reset_enabled', '1');

-- Create document_sharing table to manage document sharing between users
CREATE TABLE `document_sharing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `shared_with` int(11) NOT NULL,
  `permission` enum('view','edit','download') NOT NULL DEFAULT 'view',
  `expires_at` datetime DEFAULT NULL,
  `organization_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_user_share` (`document_id`, `shared_with`),
  KEY `shared_by` (`shared_by`),
  KEY `shared_with` (`shared_with`),
  KEY `idx_document_sharing_organization` (`organization_id`),
  CONSTRAINT `document_sharing_document_id_fk` FOREIGN KEY (`document_id`) REFERENCES `generated_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_sharing_shared_by_fk` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_sharing_shared_with_fk` FOREIGN KEY (`shared_with`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_sharing_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create document_template_variables table to store available variables for templates
CREATE TABLE `document_template_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable_name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` enum('client','consultant','application','booking','organization','system') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `variable_name` (`variable_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert common template variables
INSERT INTO `document_template_variables` (`variable_name`, `description`, `category`, `is_active`) VALUES
('client_name', 'Full name of the client', 'client', 1),
('client_email', 'Email address of the client', 'client', 1),
('client_phone', 'Phone number of the client', 'client', 1),
('consultant_name', 'Full name of the consultant', 'consultant', 1),
('consultant_email', 'Email address of the consultant', 'consultant', 1),
('consultant_phone', 'Phone number of the consultant', 'consultant', 1),
('application_reference', 'Reference number of the application', 'application', 1),
('application_status', 'Current status of the application', 'application', 1),
('booking_reference', 'Reference number of the booking', 'booking', 1),
('booking_date', 'Date of the booking', 'booking', 1),
('booking_time', 'Time of the booking', 'booking', 1),
('organization_name', 'Name of the organization', 'organization', 1),
('organization_address', 'Address of the organization', 'organization', 1),
('current_date', 'Current date', 'system', 1),
('expiry_date', 'Expiry date (for documents with validity)', 'system', 1);

-- Create document_access_logs table to track document access
CREATE TABLE `document_access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('view','download','print','share','edit') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `organization_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_document_access_logs_organization` (`organization_id`),
  CONSTRAINT `document_access_logs_document_id_fk` FOREIGN KEY (`document_id`) REFERENCES `generated_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_access_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_access_logs_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create stored procedure to generate document from template with variable substitution
DELIMITER //
CREATE PROCEDURE generate_document_from_template(
    IN p_template_id INT,
    IN p_client_id INT,
    IN p_application_id INT,
    IN p_booking_id INT,
    IN p_created_by INT,
    IN p_document_name VARCHAR(100),
    OUT p_document_id INT
)
BEGIN
    DECLARE v_template_content LONGTEXT;
    DECLARE v_document_type_id INT;
    DECLARE v_organization_id INT;
    DECLARE v_consultant_id INT;
    DECLARE v_file_path VARCHAR(255);
    DECLARE v_client_name VARCHAR(200);
    DECLARE v_client_email VARCHAR(100);
    DECLARE v_consultant_name VARCHAR(200);
    DECLARE v_application_reference VARCHAR(20);
    DECLARE v_booking_reference VARCHAR(20);
    DECLARE v_organization_name VARCHAR(100);
    
    -- Get template content and document type
    SELECT dt.content, dt.document_type_id, dt.organization_id, dt.consultant_id
    INTO v_template_content, v_document_type_id, v_organization_id, v_consultant_id
    FROM document_templates dt
    WHERE dt.id = p_template_id;
    
    -- Generate file path (in a real system, this would be more complex)
    SET v_file_path = CONCAT('/documents/', p_client_id, '/', p_template_id, '_', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '.pdf');
    
    -- Get client information
    SELECT CONCAT(u.first_name, ' ', u.last_name), u.email
    INTO v_client_name, v_client_email
    FROM users u
    WHERE u.id = p_client_id;
    
    -- Get consultant information if available
    IF v_consultant_id IS NOT NULL THEN
        SELECT CONCAT(u.first_name, ' ', u.last_name)
        INTO v_consultant_name
        FROM users u
        WHERE u.id = v_consultant_id;
    END IF;
    
    -- Get application reference if available
    IF p_application_id IS NOT NULL THEN
        SELECT a.reference_number
        INTO v_application_reference
        FROM applications a
        WHERE a.id = p_application_id;
    END IF;
    
    -- Get booking reference if available
    IF p_booking_id IS NOT NULL THEN
        SELECT b.reference_number
        INTO v_booking_reference
        FROM bookings b
        WHERE b.id = p_booking_id;
    END IF;
    
    -- Get organization name
    SELECT o.name
    INTO v_organization_name
    FROM organizations o
    WHERE o.id = v_organization_id;
    
    -- Insert the generated document
    INSERT INTO generated_documents (
        name, document_type_id, template_id, client_id, file_path, 
        created_by, generated_date, organization_id, application_id, 
        booking_id, consultant_id
    ) VALUES (
        p_document_name, v_document_type_id, p_template_id, p_client_id, 
        v_file_path, p_created_by, NOW(), v_organization_id, p_application_id,
        p_booking_id, v_consultant_id
    );
    
    -- Get the new document ID
    SET p_document_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- Create view for document statistics by organization
CREATE OR REPLACE VIEW document_statistics_view AS
SELECT 
    o.id AS organization_id,
    o.name AS organization_name,
    COUNT(DISTINCT gd.id) AS total_documents,
    COUNT(DISTINCT gd.template_id) AS templates_used,
    COUNT(DISTINCT gd.client_id) AS clients_with_documents,
    COUNT(DISTINCT gd.application_id) AS applications_with_documents,
    COUNT(DISTINCT gd.booking_id) AS bookings_with_documents,
    COUNT(DISTINCT CASE WHEN gd.email_sent = 1 THEN gd.id END) AS documents_emailed,
    COUNT(DISTINCT dal.id) AS document_views,
    DATE_FORMAT(MAX(gd.created_at), '%Y-%m-%d') AS last_document_date
FROM 
    organizations o
LEFT JOIN 
    generated_documents gd ON o.id = gd.organization_id
LEFT JOIN 
    document_access_logs dal ON gd.id = dal.document_id AND dal.action = 'view'
GROUP BY 
    o.id, o.name;

CREATE TABLE `visa_required_documents` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `visa_id` INT NOT NULL,
    `document_type_id` INT NOT NULL,
    `is_mandatory` BOOLEAN DEFAULT FALSE,
    `notes` TEXT,
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`visa_id`) REFERENCES `visas`(`visa_id`) ON DELETE CASCADE,
    FOREIGN KEY (`document_type_id`) REFERENCES `document_types`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `visa_document` (`visa_id`, `document_type_id`),
    KEY `idx_visa_required_documents_organization` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
