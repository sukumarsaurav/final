CREATE TABLE countries (
    country_id INT PRIMARY KEY AUTO_INCREMENT,
    country_name VARCHAR(100) NOT NULL,
    country_code CHAR(3) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    inactive_reason VARCHAR(255),
    inactive_since DATE,
    is_global BOOLEAN DEFAULT FALSE, -- Global countries available to all organizations
    organization_id INT NULL, -- NULL for global countries, specific ID for organization-specific countries
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY org_country_code (organization_id, country_code), -- Allow same country code for different organizations
    KEY idx_countries_organization (organization_id),
    CONSTRAINT countries_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create the visas table with country relationship
CREATE TABLE visas (
    visa_id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    visa_type VARCHAR(100) NOT NULL,
    description TEXT,
    validity_period INT, -- in days
    fee DECIMAL(10, 2),
    requirements TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    inactive_reason VARCHAR(255),
    inactive_since DATE,
    is_global BOOLEAN DEFAULT FALSE, -- Global visas available to all organizations
    organization_id INT NULL, -- NULL for global visas, specific ID for organization-specific visas
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(country_id) ON DELETE CASCADE,
    KEY idx_visas_organization (organization_id),
    CONSTRAINT visas_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create service types table with organization relationship
CREATE TABLE service_types (
    service_type_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    is_global BOOLEAN DEFAULT FALSE, -- Global service types available to all organizations
    organization_id INT NULL, -- NULL for global service types, specific ID for organization-specific types
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY org_service_name (organization_id, service_name), -- Allow same service name for different organizations
    KEY idx_service_types_organization (organization_id),
    CONSTRAINT service_types_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create consultation modes table with organization relationship
CREATE TABLE consultation_modes (
    consultation_mode_id INT PRIMARY KEY AUTO_INCREMENT,
    mode_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_custom BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_global BOOLEAN DEFAULT FALSE, -- Global modes available to all organizations
    organization_id INT NULL, -- NULL for global modes, specific ID for organization-specific modes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY org_mode_name (organization_id, mode_name), -- Allow same mode name for different organizations
    KEY idx_consultation_modes_organization (organization_id),
    CONSTRAINT consultation_modes_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create visa_services table that connects visas with service types and base pricing
CREATE TABLE visa_services (
    visa_service_id INT PRIMARY KEY AUTO_INCREMENT,
    visa_id INT NOT NULL,
    service_type_id INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    organization_id INT NOT NULL, -- Always tied to an organization
    consultant_id INT NOT NULL, -- The consultant who created/manages this service
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visa_id) REFERENCES visas(visa_id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types(service_type_id) ON DELETE CASCADE,
    FOREIGN KEY (consultant_id) REFERENCES consultants(user_id) ON DELETE CASCADE,
    -- Ensure unique combination of visa, service type and organization
    UNIQUE KEY (visa_id, service_type_id, organization_id),
    KEY idx_visa_services_organization (organization_id),
    KEY idx_visa_services_consultant (consultant_id),
    CONSTRAINT visa_services_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create service_consultation_modes table to link services with available consultation modes and their additional fees
CREATE TABLE service_consultation_modes (
    service_consultation_id INT PRIMARY KEY AUTO_INCREMENT,
    visa_service_id INT NOT NULL,
    consultation_mode_id INT NOT NULL,
    additional_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    duration_minutes INT,
    is_available BOOLEAN DEFAULT TRUE,
    organization_id INT NOT NULL, -- Always tied to an organization
    consultant_id INT NOT NULL, -- The consultant who created/manages this consultation mode
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visa_service_id) REFERENCES visa_services(visa_service_id) ON DELETE CASCADE,
    FOREIGN KEY (consultation_mode_id) REFERENCES consultation_modes(consultation_mode_id) ON DELETE CASCADE,
    FOREIGN KEY (consultant_id) REFERENCES consultants(user_id) ON DELETE CASCADE,
    -- Ensure unique combination of service, consultation mode and organization
    UNIQUE KEY (visa_service_id, consultation_mode_id, organization_id),
    KEY idx_service_consultation_modes_organization (organization_id),
    KEY idx_service_consultation_modes_consultant (consultant_id),
    CONSTRAINT service_consultation_modes_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create consultant_services table to track which services each consultant offers
CREATE TABLE consultant_services (
    consultant_service_id INT PRIMARY KEY AUTO_INCREMENT,
    consultant_id INT NOT NULL,
    visa_service_id INT NOT NULL,
    custom_price DECIMAL(10, 2) NULL, -- Optional override of base price for this consultant
    is_active BOOLEAN DEFAULT TRUE,
    organization_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consultant_id) REFERENCES consultants(user_id) ON DELETE CASCADE,
    FOREIGN KEY (visa_service_id) REFERENCES visa_services(visa_service_id) ON DELETE CASCADE,
    UNIQUE KEY (consultant_id, visa_service_id),
    KEY idx_consultant_services_organization (organization_id),
    CONSTRAINT consultant_services_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Create service_documents table to track required documents for each service
CREATE TABLE service_documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    visa_service_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    is_required BOOLEAN DEFAULT TRUE,
    description TEXT,
    organization_id INT NOT NULL,
    consultant_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (visa_service_id) REFERENCES visa_services(visa_service_id) ON DELETE CASCADE,
    FOREIGN KEY (consultant_id) REFERENCES consultants(user_id) ON DELETE CASCADE,
    KEY idx_service_documents_organization (organization_id),
    CONSTRAINT service_documents_organization_id_fk FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Insert default global consultation modes
INSERT INTO consultation_modes (mode_name, description, is_custom, is_active, is_global) VALUES
('In-Person', 'Face-to-face consultation at our office', FALSE, TRUE, TRUE),
('Video Call', 'Online consultation via video conferencing', FALSE, TRUE, TRUE),
('Phone Call', 'Consultation over the phone', FALSE, TRUE, TRUE),
('Email', 'Consultation through email exchanges', FALSE, TRUE, TRUE),
('Chat', 'Instant messaging consultation', FALSE, TRUE, TRUE);

-- Insert default global service types
INSERT INTO service_types (service_name, description, is_active, is_global) VALUES
('Initial Consultation', 'First meeting to discuss visa options and requirements', TRUE, TRUE),
('Document Review', 'Review and verification of application documents', TRUE, TRUE),
('Application Preparation', 'Complete preparation of visa application', TRUE, TRUE),
('Application Submission', 'Submission of visa application to authorities', TRUE, TRUE),
('Appeal Preparation', 'Preparation of appeal documents for rejected applications', TRUE, TRUE),
('Full Service Package', 'Complete end-to-end visa application service', TRUE, TRUE);

-- Add organization_id to all relevant tables
ALTER TABLE `visa_services` 
    ADD COLUMN `is_bookable` BOOLEAN DEFAULT TRUE AFTER `is_active`,
    ADD COLUMN `booking_instructions` TEXT AFTER `is_bookable`;

-- Add consultant availability table for specific service offerings
CREATE TABLE `consultant_availability` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `consultant_id` INT NOT NULL,
    `visa_service_id` INT NOT NULL,
    `day_of_week` TINYINT(1) NOT NULL COMMENT '0 = Sunday, 1 = Monday, etc.',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `max_bookings` INT DEFAULT NULL COMMENT 'Maximum number of bookings allowed in this time slot',
    `is_available` BOOLEAN DEFAULT TRUE,
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services`(`visa_service_id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`consultant_id`, `visa_service_id`, `day_of_week`),
    KEY `idx_consultant_availability_organization` (`organization_id`)
);

-- Add consultant blocked dates for specific services
CREATE TABLE `consultant_blocked_dates` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `consultant_id` INT NOT NULL,
    `visa_service_id` INT NULL COMMENT 'NULL means blocked for all services',
    `blocked_date` DATE NOT NULL,
    `reason` VARCHAR(255),
    `is_recurring` BOOLEAN DEFAULT FALSE COMMENT 'If true, blocks this date every year',
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services`(`visa_service_id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`consultant_id`, `visa_service_id`, `blocked_date`),
    KEY `idx_consultant_blocked_dates_organization` (`organization_id`)
);

-- Create service booking settings table
CREATE TABLE `service_booking_settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `visa_service_id` INT NOT NULL,
    `min_notice_hours` INT DEFAULT 24 COMMENT 'Minimum hours of notice required for booking',
    `max_advance_days` INT DEFAULT 90 COMMENT 'Maximum days in advance that can be booked',
    `buffer_before_minutes` INT DEFAULT 0 COMMENT 'Buffer time before appointment',
    `buffer_after_minutes` INT DEFAULT 0 COMMENT 'Buffer time after appointment',
    `cancellation_policy` TEXT,
    `reschedule_policy` TEXT,
    `payment_required` BOOLEAN DEFAULT FALSE COMMENT 'Whether payment is required at booking time',
    `deposit_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Deposit amount if required',
    `deposit_percentage` INT DEFAULT 0 COMMENT 'Or percentage of total price',
    `organization_id` INT NOT NULL,
    `consultant_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services`(`visa_service_id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
    UNIQUE KEY (`visa_service_id`),
    KEY `idx_service_booking_settings_organization` (`organization_id`),
    KEY `idx_service_booking_settings_consultant` (`consultant_id`)
);

-- Create function to check if a service is available for booking at a specific time
DELIMITER //
CREATE FUNCTION is_service_available_for_booking(
    p_visa_service_id INT,
    p_consultant_id INT,
    p_booking_date DATE,
    p_start_time TIME,
    p_end_time TIME
) RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE v_is_available BOOLEAN DEFAULT FALSE;
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_day_of_week TINYINT;
    DECLARE v_organization_id INT;
    
    -- Get the day of week (0 = Sunday, 1 = Monday, etc.)
    SET v_day_of_week = WEEKDAY(p_booking_date);
    IF v_day_of_week = 6 THEN 
        SET v_day_of_week = 0; -- Sunday
    ELSE 
        SET v_day_of_week = v_day_of_week + 1; -- Other days
    END IF;
    
    -- Get organization_id
    SELECT organization_id INTO v_organization_id 
    FROM visa_services 
    WHERE visa_service_id = p_visa_service_id;
    
    -- Check if service is active and bookable
    SELECT COUNT(*) INTO v_count 
    FROM visa_services 
    WHERE visa_service_id = p_visa_service_id 
    AND consultant_id = p_consultant_id
    AND is_active = 1 
    AND is_bookable = 1;
    
    IF v_count = 0 THEN
        RETURN FALSE;
    END IF;
    
    -- Check if date is blocked
    SELECT COUNT(*) INTO v_count 
    FROM consultant_blocked_dates 
    WHERE consultant_id = p_consultant_id 
    AND (visa_service_id = p_visa_service_id OR visa_service_id IS NULL)
    AND (
        blocked_date = p_booking_date 
        OR (is_recurring = 1 AND MONTH(blocked_date) = MONTH(p_booking_date) AND DAY(blocked_date) = DAY(p_booking_date))
    );
    
    IF v_count > 0 THEN
        RETURN FALSE;
    END IF;
    
    -- Check if within consultant's availability for this service
    SELECT COUNT(*) INTO v_count 
    FROM consultant_availability 
    WHERE consultant_id = p_consultant_id 
    AND visa_service_id = p_visa_service_id
    AND day_of_week = v_day_of_week
    AND is_available = 1
    AND p_start_time >= start_time
    AND p_end_time <= end_time;
    
    IF v_count > 0 THEN
        -- Check if maximum bookings for this slot is not exceeded
        SELECT ca.max_bookings INTO v_count 
        FROM consultant_availability ca
        WHERE ca.consultant_id = p_consultant_id 
        AND ca.visa_service_id = p_visa_service_id
        AND ca.day_of_week = v_day_of_week;
        
        IF v_count IS NOT NULL THEN
            SELECT COUNT(*) INTO v_count 
            FROM bookings b
            JOIN booking_statuses bs ON b.status_id = bs.id
            WHERE b.consultant_id = p_consultant_id 
            AND b.visa_service_id = p_visa_service_id
            AND DATE(b.booking_datetime) = p_booking_date
            AND TIME(b.booking_datetime) >= p_start_time
            AND TIME(b.end_datetime) <= p_end_time
            AND bs.name IN ('pending', 'confirmed')
            AND b.deleted_at IS NULL;
            
            IF v_count >= v_count THEN
                RETURN FALSE;
            END IF;
        END IF;
        
        RETURN TRUE;
    END IF;
    
    RETURN FALSE;
END //
DELIMITER ;

-- Add service ratings and reviews table
CREATE TABLE `service_reviews` (
    `review_id` INT PRIMARY KEY AUTO_INCREMENT,
    `visa_service_id` INT NOT NULL,
    `user_id` INT NOT NULL COMMENT 'Client who left the review',
    `booking_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    `review_text` TEXT,
    `is_verified` BOOLEAN DEFAULT FALSE COMMENT 'Verified if client actually used the service',
    `is_public` BOOLEAN DEFAULT TRUE,
    `consultant_response` TEXT,
    `responded_at` DATETIME,
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services`(`visa_service_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`booking_id`),
    KEY `idx_service_reviews_visa_service` (`visa_service_id`),
    KEY `idx_service_reviews_organization` (`organization_id`)
);

-- Add service packages table for bundled services
CREATE TABLE `service_packages` (
    `package_id` INT PRIMARY KEY AUTO_INCREMENT,
    `package_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `total_price` DECIMAL(10, 2) NOT NULL,
    `discount_percentage` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `consultant_id` INT NOT NULL,
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    KEY `idx_service_packages_consultant` (`consultant_id`),
    KEY `idx_service_packages_organization` (`organization_id`)
);

-- Add service package items table
CREATE TABLE `service_package_items` (
    `package_item_id` INT PRIMARY KEY AUTO_INCREMENT,
    `package_id` INT NOT NULL,
    `visa_service_id` INT NOT NULL,
    `service_consultation_id` INT NOT NULL,
    `item_order` INT NOT NULL COMMENT 'Order of services in the package',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`package_id`) REFERENCES `service_packages`(`package_id`) ON DELETE CASCADE,
    FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services`(`visa_service_id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_consultation_id`) REFERENCES `service_consultation_modes`(`service_consultation_id`) ON DELETE CASCADE,
    UNIQUE KEY (`package_id`, `visa_service_id`)
);

-- Add service availability slots for specific dates
CREATE TABLE `service_availability_slots` (
    `slot_id` INT PRIMARY KEY AUTO_INCREMENT,
    `consultant_id` INT NOT NULL,
    `visa_service_id` INT NOT NULL,
    `slot_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `max_bookings` INT DEFAULT 1,
    `current_bookings` INT DEFAULT 0,
    `is_available` BOOLEAN DEFAULT TRUE,
    `organization_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultant_id`) REFERENCES `consultants`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`visa_service_id`) REFERENCES `visa_services`(`visa_service_id`) ON DELETE CASCADE,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`consultant_id`, `visa_service_id`, `slot_date`, `start_time`),
    KEY `idx_service_slots_organization` (`organization_id`)
);

-- Add trigger to update current_bookings count when a booking is created or updated
DELIMITER //
CREATE TRIGGER update_slot_bookings_after_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    -- Find the slot that matches this booking
    UPDATE service_availability_slots
    SET current_bookings = current_bookings + 1
    WHERE consultant_id = NEW.consultant_id
    AND visa_service_id = NEW.visa_service_id
    AND slot_date = DATE(NEW.booking_datetime)
    AND start_time <= TIME(NEW.booking_datetime)
    AND end_time >= TIME(NEW.end_datetime);
END //
DELIMITER ;

-- Add trigger to update current_bookings count when a booking is cancelled or deleted
DELIMITER //
CREATE TRIGGER update_slot_bookings_after_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    -- If booking was cancelled or deleted
    IF (NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL) OR 
       (NEW.status_id != OLD.status_id AND (
           SELECT name FROM booking_statuses WHERE id = NEW.status_id
       ) IN ('cancelled_by_user', 'cancelled_by_admin', 'cancelled_by_consultant')) THEN
        
        -- Decrease the booking count
        UPDATE service_availability_slots
        SET current_bookings = GREATEST(0, current_bookings - 1)
        WHERE consultant_id = NEW.consultant_id
        AND visa_service_id = NEW.visa_service_id
        AND slot_date = DATE(NEW.booking_datetime)
        AND start_time <= TIME(NEW.booking_datetime)
        AND end_time >= TIME(NEW.end_datetime);
    END IF;
END //
DELIMITER ;

-- Add stored procedure to generate availability slots for a consultant
DELIMITER //
CREATE PROCEDURE generate_consultant_availability_slots(
    IN p_consultant_id INT,
    IN p_visa_service_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE v_current_date DATE;
    DECLARE v_day_of_week INT;
    DECLARE v_start_time TIME;
    DECLARE v_end_time TIME;
    DECLARE v_max_bookings INT;
    DECLARE v_organization_id INT;
    DECLARE done INT DEFAULT FALSE;
    
    -- Cursor to get availability for each day of week
    DECLARE cur CURSOR FOR 
        SELECT day_of_week, start_time, end_time, IFNULL(max_bookings, 1)
        FROM consultant_availability
        WHERE consultant_id = p_consultant_id
        AND visa_service_id = p_visa_service_id
        AND is_available = 1;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Get organization_id
    SELECT organization_id INTO v_organization_id
    FROM users
    WHERE id = p_consultant_id;
    
    -- Open cursor
    OPEN cur;
    
    -- Loop through each availability rule
    read_loop: LOOP
        FETCH cur INTO v_day_of_week, v_start_time, v_end_time, v_max_bookings;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Set current date to start date
        SET v_current_date = p_start_date;
        
        -- Loop through each day in the date range
        WHILE v_current_date <= p_end_date DO
            -- Check if current date matches day of week from availability
            IF WEEKDAY(v_current_date) = v_day_of_week - 1 OR 
               (WEEKDAY(v_current_date) = 6 AND v_day_of_week = 0) THEN
                
                -- Check if date is not blocked
                IF NOT EXISTS (
                    SELECT 1 FROM consultant_blocked_dates
                    WHERE consultant_id = p_consultant_id
                    AND (visa_service_id = p_visa_service_id OR visa_service_id IS NULL)
                    AND (
                        blocked_date = v_current_date
                        OR (is_recurring = 1 AND MONTH(blocked_date) = MONTH(v_current_date) AND DAY(blocked_date) = DAY(v_current_date))
                    )
                ) THEN
                    -- Insert availability slot
                    INSERT IGNORE INTO service_availability_slots
                    (consultant_id, visa_service_id, slot_date, start_time, end_time, max_bookings, organization_id)
                    VALUES
                    (p_consultant_id, p_visa_service_id, v_current_date, v_start_time, v_end_time, v_max_bookings, v_organization_id);
                END IF;
            END IF;
            
            -- Move to next day
            SET v_current_date = DATE_ADD(v_current_date, INTERVAL 1 DAY);
        END WHILE;
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;