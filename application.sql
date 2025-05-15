-- Applications status table
CREATE TABLE `application_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `color` varchar(7) DEFAULT '#808080' COMMENT 'Hex color code for UI display',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default application statuses
INSERT INTO `application_statuses` (`name`, `description`, `color`) VALUES
('draft', 'Application is being drafted', '#FFA500'),
('submitted', 'Application has been submitted', '#1E90FF'),
('under_review', 'Application is under review', '#9932CC'),
('additional_documents_requested', 'Additional documents have been requested', '#FF8C00'),
('processing', 'Application is being processed', '#4682B4'),
('approved', 'Application has been approved', '#008000'),
('rejected', 'Application has been rejected', '#FF0000'),
('on_hold', 'Application is on hold', '#808080'),
('completed', 'Application process has been completed', '#0000FF'),
('cancelled', 'Application has been cancelled', '#8B0000');

-- Required documents for visa types
CREATE TABLE `visa_required_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visa_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `visa_document` (`visa_id`, `document_type_id`),
  KEY `document_type_id` (`document_type_id`),
  CONSTRAINT `visa_required_documents_visa_id_fk` FOREIGN KEY (`visa_id`) REFERENCES `visas` (`visa_id`) ON DELETE CASCADE,
  CONSTRAINT `visa_required_documents_document_type_id_fk` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Main applications table
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(20) NOT NULL COMMENT 'Unique application reference for tracking',
  `user_id` int(11) NOT NULL COMMENT 'Applicant',
  `visa_id` int(11) NOT NULL COMMENT 'Visa being applied for',
  `status_id` int(11) NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Internal notes for application',
  `expected_completion_date` date DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `created_by` int(11) NOT NULL COMMENT 'Admin who created the application',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `organization_id` int(11) NOT NULL,
  `consultant_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `user_id` (`user_id`),
  KEY `visa_id` (`visa_id`),
  KEY `status_id` (`status_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_applications_priority` (`priority`),
  KEY `idx_applications_organization` (`organization_id`),
  KEY `idx_applications_consultant` (`consultant_id`),
  CONSTRAINT `applications_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_visa_id_fk` FOREIGN KEY (`visa_id`) REFERENCES `visas` (`visa_id`) ON DELETE CASCADE,
  CONSTRAINT `applications_status_id_fk` FOREIGN KEY (`status_id`) REFERENCES `application_statuses` (`id`),
  CONSTRAINT `applications_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `applications_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_consultant_id_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Application documents
CREATE TABLE `application_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','submitted','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `organization_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_document` (`application_id`, `document_type_id`),
  KEY `document_type_id` (`document_type_id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_document_status` (`status`),
  KEY `idx_application_documents_organization` (`organization_id`),
  CONSTRAINT `application_documents_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_documents_document_type_id_fk` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_documents_submitted_by_fk` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `application_documents_reviewed_by_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `application_documents_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Application status history
CREATE TABLE `application_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `status_id` (`status_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `application_status_history_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_status_history_status_id_fk` FOREIGN KEY (`status_id`) REFERENCES `application_statuses` (`id`),
  CONSTRAINT `application_status_history_changed_by_fk` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Application comments
CREATE TABLE `application_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If true, only visible to team members',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `application_comments_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_comments_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Create application assignments junction table
CREATE TABLE `application_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `team_member_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','reassigned') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `team_member_id` (`team_member_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `app_assignments_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `app_assignments_team_member_id_fk` FOREIGN KEY (`team_member_id`) REFERENCES `team_members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `app_assignments_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Application activity logs
CREATE TABLE `application_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('created','updated','status_changed','document_added','document_updated','comment_added','assigned','completed') NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  CONSTRAINT `application_activity_logs_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_activity_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add booking_id to applications to link applications with bookings
ALTER TABLE `applications`
  ADD COLUMN `booking_id` int(11) NULL AFTER `visa_id`,
  ADD KEY `idx_applications_booking` (`booking_id`),
  ADD CONSTRAINT `applications_booking_id_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

-- Create application_service_links table to track which services are related to an application
CREATE TABLE `application_service_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `visa_service_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_service` (`application_id`, `visa_service_id`),
  KEY `visa_service_id` (`visa_service_id`),
  CONSTRAINT `application_service_links_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_service_links_visa_service_id_fk` FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services` (`visa_service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add document_types table which was referenced but not defined
CREATE TABLE `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_global` BOOLEAN DEFAULT FALSE,
  `organization_id` int(11) NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_document_name` (`organization_id`, `name`),
  KEY `idx_document_types_organization` (`organization_id`),
  CONSTRAINT `document_types_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default global document types
INSERT INTO `document_types` (`name`, `description`, `is_global`) VALUES
('Passport', 'Valid passport with at least 6 months validity', TRUE),
('ID Card', 'National identity card', TRUE),
('Birth Certificate', 'Official birth certificate', TRUE),
('Marriage Certificate', 'Official marriage certificate', TRUE),
('Bank Statement', 'Bank statements showing financial history', TRUE),
('Employment Letter', 'Letter from employer confirming employment', TRUE),
('Education Certificates', 'Academic qualifications and certificates', TRUE),
('Medical Certificate', 'Medical examination results', TRUE),
('Police Clearance', 'Police clearance certificate', TRUE),
('Visa Photo', 'Passport-sized photographs meeting visa requirements', TRUE);

-- Add organization_id to visa_required_documents table
ALTER TABLE `visa_required_documents`
  ADD COLUMN `organization_id` int(11) NULL AFTER `notes`,
  ADD KEY `idx_visa_required_documents_organization` (`organization_id`),
  ADD CONSTRAINT `visa_required_documents_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;

-- Create application_templates table for standard application templates
CREATE TABLE `application_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `visa_id` int(11) NOT NULL,
  `estimated_processing_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `organization_id` int(11) NOT NULL,
  `consultant_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `visa_id` (`visa_id`),
  KEY `idx_application_templates_organization` (`organization_id`),
  KEY `idx_application_templates_consultant` (`consultant_id`),
  CONSTRAINT `application_templates_visa_id_fk` FOREIGN KEY (`visa_id`) REFERENCES `visas` (`visa_id`) ON DELETE CASCADE,
  CONSTRAINT `application_templates_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_templates_consultant_id_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create application_template_documents table for documents required by templates
CREATE TABLE `application_template_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `instructions` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_document` (`template_id`, `document_type_id`),
  KEY `document_type_id` (`document_type_id`),
  CONSTRAINT `template_documents_template_id_fk` FOREIGN KEY (`template_id`) REFERENCES `application_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `template_documents_document_type_id_fk` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create application_service_packages table to link applications with service packages
CREATE TABLE `application_service_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_package` (`application_id`, `package_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `application_packages_application_id_fk` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_packages_package_id_fk` FOREIGN KEY (`package_id`) REFERENCES `service_packages` (`package_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create a view for applications with their related services
CREATE OR REPLACE VIEW application_services_view AS
SELECT 
    a.id AS application_id,
    a.reference_number,
    a.user_id AS applicant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS applicant_name,
    a.visa_id,
    v.visa_type,
    co.country_name,
    vs.visa_service_id,
    st.service_name,
    a.status_id,
    aps.name AS status_name,
    a.booking_id,
    b.reference_number AS booking_reference,
    a.organization_id,
    o.name AS organization_name,
    a.consultant_id,
    CONCAT(cu.first_name, ' ', cu.last_name) AS consultant_name,
    a.created_at,
    a.submitted_at
FROM 
    applications a
JOIN 
    users u ON a.user_id = u.id
JOIN 
    visas v ON a.visa_id = v.visa_id
JOIN 
    countries co ON v.country_id = co.country_id
JOIN 
    application_statuses aps ON a.status_id = aps.id
JOIN 
    organizations o ON a.organization_id = o.id
JOIN 
    users cu ON a.consultant_id = cu.id
LEFT JOIN 
    application_service_links asl ON a.id = asl.application_id
LEFT JOIN 
    visa_services vs ON asl.visa_service_id = vs.visa_service_id
LEFT JOIN 
    service_types st ON vs.service_type_id = st.service_type_id
LEFT JOIN 
    bookings b ON a.booking_id = b.id
WHERE 
    a.deleted_at IS NULL
ORDER BY 
    a.created_at DESC;

-- Create function to generate a unique application reference number
DELIMITER //
CREATE FUNCTION generate_application_reference() 
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE v_ref VARCHAR(20);
    DECLARE v_exists INT;
    
    SET v_exists = 1;
    
    WHILE v_exists > 0 DO
        -- Generate reference: APP + year + random 7 digits
        SET v_ref = CONCAT('APP', DATE_FORMAT(NOW(), '%y'), LPAD(FLOOR(RAND() * 10000000), 7, '0'));
        
        -- Check if it exists
        SELECT COUNT(*) INTO v_exists FROM applications WHERE reference_number = v_ref;
    END WHILE;
    
    RETURN v_ref;
END //
DELIMITER ;

-- Trigger to automatically generate application reference number
DELIMITER //
CREATE TRIGGER before_application_insert
BEFORE INSERT ON applications
FOR EACH ROW
BEGIN
    IF NEW.reference_number IS NULL OR NEW.reference_number = '' THEN
        SET NEW.reference_number = generate_application_reference();
    END IF;
END //
DELIMITER ;

-- Create stored procedure to create application from booking
DELIMITER //
CREATE PROCEDURE create_application_from_booking(
    IN p_booking_id INT,
    IN p_created_by INT,
    OUT p_application_id INT
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_visa_id INT;
    DECLARE v_visa_service_id INT;
    DECLARE v_organization_id INT;
    DECLARE v_consultant_id INT;
    DECLARE v_status_id INT;
    
    -- Get draft status ID
    SELECT id INTO v_status_id FROM application_statuses WHERE name = 'draft' LIMIT 1;
    
    -- Get booking data
    SELECT 
        b.user_id, vs.visa_id, b.visa_service_id, 
        b.organization_id, b.consultant_id
    INTO 
        v_user_id, v_visa_id, v_visa_service_id, 
        v_organization_id, v_consultant_id
    FROM bookings b
    JOIN visa_services vs ON b.visa_service_id = vs.visa_service_id
    WHERE b.id = p_booking_id;
    
    -- Insert application
    INSERT INTO applications (
        user_id, visa_id, booking_id, status_id, 
        created_by, organization_id, consultant_id
    ) VALUES (
        v_user_id, v_visa_id, p_booking_id, v_status_id,
        p_created_by, v_organization_id, v_consultant_id
    );
    
    -- Get the new application ID
    SET p_application_id = LAST_INSERT_ID();
    
    -- Link application with the service
    INSERT INTO application_service_links (
        application_id, visa_service_id
    ) VALUES (
        p_application_id, v_visa_service_id
    );
    
    -- Create activity log entry
    INSERT INTO application_activity_logs (
        application_id, user_id, activity_type, description
    ) VALUES (
        p_application_id, p_created_by, 'created', 'Application created from booking'
    );
    
    -- Add required documents based on visa requirements
    INSERT INTO application_documents (
        application_id, document_type_id, status, organization_id
    )
    SELECT 
        p_application_id, vrd.document_type_id, 'pending', v_organization_id
    FROM 
        visa_required_documents vrd
    WHERE 
        vrd.visa_id = v_visa_id;
END //
DELIMITER ;

-- Create stored procedure to assign application to team member
DELIMITER //
CREATE PROCEDURE assign_application_to_team_member(
    IN p_application_id INT,
    IN p_team_member_id INT,
    IN p_assigned_by INT,
    IN p_notes TEXT
)
BEGIN
    -- Check if application is already assigned to someone
    UPDATE application_assignments
    SET status = 'reassigned'
    WHERE application_id = p_application_id AND status = 'active';
    
    -- Create new assignment
    INSERT INTO application_assignments (
        application_id, team_member_id, assigned_by, notes
    ) VALUES (
        p_application_id, p_team_member_id, p_assigned_by, p_notes
    );
    
    -- Create activity log entry
    INSERT INTO application_activity_logs (
        application_id, user_id, activity_type, description
    ) VALUES (
        p_application_id, p_assigned_by, 'assigned', 
        CONCAT('Application assigned to team member ID ', p_team_member_id)
    );
END //
DELIMITER ;
