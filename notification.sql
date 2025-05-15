-- Notification Types table to define categories of notifications
CREATE TABLE `notification_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'System notifications cannot be modified/deleted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default notification types
INSERT INTO `notification_types` (`type_name`, `description`, `icon`, `color`, `is_system`) VALUES
('booking_created', 'New booking created', 'calendar-plus', '#4CAF50', 1),
('booking_confirmed', 'Booking has been confirmed', 'calendar-check', '#2196F3', 1),
('booking_cancelled', 'Booking has been cancelled', 'calendar-times', '#F44336', 1),
('booking_rescheduled', 'Booking has been rescheduled', 'calendar-day', '#FF9800', 1),
('booking_reminder', 'Booking reminder', 'bell', '#9C27B0', 1),
('message_received', 'New message received', 'envelope', '#03A9F4', 1),
('application_status_update', 'Application status has been updated', 'file-alt', '#3F51B5', 1),
('document_uploaded', 'New document has been uploaded', 'file-upload', '#009688', 1),
('document_approved', 'Document has been approved', 'file-check', '#4CAF50', 1),
('document_rejected', 'Document has been rejected', 'file-exclamation', '#F44336', 1),
('ai_chat_response', 'New AI chat response', 'robot', '#607D8B', 1),
('payment_received', 'Payment has been received', 'credit-card', '#8BC34A', 1),
('refund_processed', 'Refund has been processed', 'money-bill-wave', '#FF5722', 1),
('team_assignment', 'Assigned to team member', 'user-plus', '#795548', 1),
('reminder', 'General reminder', 'clock', '#9E9E9E', 1);

-- Main notifications table
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'User receiving the notification',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `dismissed_at` datetime DEFAULT NULL,
  `action_link` varchar(255) DEFAULT NULL COMMENT 'URL to redirect when clicked',
  `related_booking_id` int(11) DEFAULT NULL,
  `related_application_id` int(11) DEFAULT NULL,
  `related_conversation_id` int(11) DEFAULT NULL,
  `related_message_id` int(11) DEFAULT NULL,
  `related_document_id` int(11) DEFAULT NULL,
  `related_ai_chat_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'User or system that created the notification',
  `expires_at` datetime DEFAULT NULL COMMENT 'When notification should expire/auto-dismiss',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  KEY `user_id` (`user_id`),
  KEY `related_booking_id` (`related_booking_id`),
  KEY `related_application_id` (`related_application_id`),
  KEY `related_conversation_id` (`related_conversation_id`),
  KEY `related_message_id` (`related_message_id`),
  KEY `related_document_id` (`related_document_id`),
  KEY `related_ai_chat_id` (`related_ai_chat_id`),
  KEY `organization_id` (`organization_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_expires_at` (`expires_at`),
  CONSTRAINT `notifications_type_id_fk` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`),
  CONSTRAINT `notifications_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_booking_id_fk` FOREIGN KEY (`related_booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_application_id_fk` FOREIGN KEY (`related_application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_conversation_id_fk` FOREIGN KEY (`related_conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_message_id_fk` FOREIGN KEY (`related_message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_document_id_fk` FOREIGN KEY (`related_document_id`) REFERENCES `generated_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ai_chat_id_fk` FOREIGN KEY (`related_ai_chat_id`) REFERENCES `ai_chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification settings per user
CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `in_app_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_type` (`user_id`, `type_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `notification_settings_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_settings_type_id_fk` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification channels table for multi-channel delivery
CREATE TABLE `notification_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `channel_type` enum('email','sms','push','webhook') NOT NULL,
  `channel_value` varchar(255) NOT NULL COMMENT 'Email address, phone number, device token, or webhook URL',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_channel_value` (`user_id`, `channel_type`, `channel_value`),
  KEY `idx_notification_channels_user` (`user_id`),
  KEY `idx_notification_channels_type` (`channel_type`),
  CONSTRAINT `notification_channels_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification delivery tracking table
CREATE TABLE `notification_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `status` enum('pending','sent','delivered','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `delivery_data` text DEFAULT NULL COMMENT 'JSON data from delivery provider',
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `channel_id` (`channel_id`),
  KEY `idx_notification_deliveries_status` (`status`),
  CONSTRAINT `notification_deliveries_notification_id_fk` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_deliveries_channel_id_fk` FOREIGN KEY (`channel_id`) REFERENCES `notification_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification templates for consistent messaging
CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `email_content` text DEFAULT NULL,
  `sms_content` text DEFAULT NULL,
  `push_content` text DEFAULT NULL,
  `in_app_content` text DEFAULT NULL,
  `variables` text DEFAULT NULL COMMENT 'JSON of available variables for this template',
  `organization_id` int(11) DEFAULT NULL COMMENT 'NULL for system templates, ID for org-specific ones',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_type_template` (`organization_id`, `type_id`, `template_name`),
  KEY `type_id` (`type_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_notification_templates_organization` (`organization_id`),
  CONSTRAINT `notification_templates_type_id_fk` FOREIGN KEY (`type_id`) REFERENCES `notification_types` (`id`),
  CONSTRAINT `notification_templates_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_templates_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default templates for common notifications
INSERT INTO `notification_templates` (`type_id`, `template_name`, `subject`, `email_content`, `sms_content`, `push_content`, `in_app_content`, `variables`) VALUES
((SELECT id FROM notification_types WHERE type_name = 'booking_created'), 'New Booking', 'New Booking: {{service_name}}', 
 '<p>Hello {{recipient_name}},</p><p>A new booking has been created for {{service_name}} on {{booking_date}} at {{booking_time}}.</p><p>Reference: {{booking_reference}}</p>', 
 'New booking: {{service_name}} on {{booking_date}} at {{booking_time}}. Ref: {{booking_reference}}', 
 'New booking: {{service_name}}', 
 'A new booking has been created for {{service_name}} on {{booking_date}} at {{booking_time}}.',
 '{"recipient_name":"Recipient full name","service_name":"Service name","booking_date":"Booking date","booking_time":"Booking time","booking_reference":"Booking reference number"}'),

((SELECT id FROM notification_types WHERE type_name = 'booking_confirmed'), 'Booking Confirmed', 'Your Booking is Confirmed: {{service_name}}', 
 '<p>Hello {{recipient_name}},</p><p>Your booking for {{service_name}} on {{booking_date}} at {{booking_time}} has been confirmed.</p><p>Reference: {{booking_reference}}</p>', 
 'Your booking for {{service_name}} on {{booking_date}} at {{booking_time}} is confirmed. Ref: {{booking_reference}}', 
 'Booking confirmed: {{service_name}}', 
 'Your booking for {{service_name}} on {{booking_date}} at {{booking_time}} has been confirmed.',
 '{"recipient_name":"Recipient full name","service_name":"Service name","booking_date":"Booking date","booking_time":"Booking time","booking_reference":"Booking reference number"}'),

((SELECT id FROM notification_types WHERE type_name = 'message_received'), 'New Message', 'New Message from {{sender_name}}', 
 '<p>Hello {{recipient_name}},</p><p>You have received a new message from {{sender_name}}:</p><p>{{message_preview}}</p>', 
 'New message from {{sender_name}}: {{message_preview}}', 
 'New message from {{sender_name}}', 
 '{{sender_name}}: {{message_preview}}',
 '{"recipient_name":"Recipient full name","sender_name":"Sender full name","message_preview":"Preview of the message content"}'),

((SELECT id FROM notification_types WHERE type_name = 'ai_chat_response'), 'AI Chat Response', 'New Response in Your AI Chat', 
 '<p>Hello {{recipient_name}},</p><p>There is a new response in your AI chat session.</p>', 
 'New response in your AI chat session.', 
 'New AI chat response', 
 'There is a new response in your AI chat session.',
 '{"recipient_name":"Recipient full name"}');

-- Create stored procedure to send a notification
DELIMITER //
CREATE PROCEDURE send_notification(
    IN p_type_name VARCHAR(50),
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_message TEXT,
    IN p_action_link VARCHAR(255),
    IN p_related_booking_id INT,
    IN p_related_application_id INT,
    IN p_related_conversation_id INT,
    IN p_related_message_id INT,
    IN p_related_document_id INT,
    IN p_related_ai_chat_id INT,
    IN p_organization_id INT,
    IN p_created_by INT,
    OUT p_notification_id INT
)
BEGIN
    DECLARE v_type_id INT;
    
    -- Get notification type ID
    SELECT id INTO v_type_id FROM notification_types WHERE type_name = p_type_name;
    
    -- Insert notification
    INSERT INTO notifications (
        type_id, user_id, title, message, action_link,
        related_booking_id, related_application_id, related_conversation_id,
        related_message_id, related_document_id, related_ai_chat_id,
        organization_id, created_by
    ) VALUES (
        v_type_id, p_user_id, p_title, p_message, p_action_link,
        p_related_booking_id, p_related_application_id, p_related_conversation_id,
        p_related_message_id, p_related_document_id, p_related_ai_chat_id,
        p_organization_id, p_created_by
    );
    
    -- Get new notification ID
    SET p_notification_id = LAST_INSERT_ID();
    
    -- Check user notification settings and queue delivery to appropriate channels
    INSERT INTO notification_deliveries (notification_id, channel_id, status)
    SELECT p_notification_id, nc.id, 'pending'
    FROM notification_settings ns
    JOIN notification_channels nc ON ns.user_id = nc.user_id
    WHERE ns.user_id = p_user_id 
    AND ns.type_id = v_type_id
    AND (
        (ns.email_enabled = 1 AND nc.channel_type = 'email') OR
        (ns.sms_enabled = 1 AND nc.channel_type = 'sms') OR
        (ns.push_enabled = 1 AND nc.channel_type = 'push')
    );
END //
DELIMITER ;

-- Create triggers for automatic notifications on various events
-- Booking confirmation trigger
DELIMITER //
CREATE TRIGGER after_booking_status_change
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    DECLARE v_status_name VARCHAR(50);
    DECLARE v_service_name VARCHAR(255);
    DECLARE v_user_name VARCHAR(255);
    DECLARE v_consultant_name VARCHAR(255);
    DECLARE v_notification_id INT;
    DECLARE v_template_id INT;
    
    -- Check if status has changed
    IF NEW.status_id != OLD.status_id THEN
        -- Get status name
        SELECT name INTO v_status_name FROM booking_statuses WHERE id = NEW.status_id;
        
        -- Get service name
        SELECT st.service_name INTO v_service_name
        FROM visa_services vs
        JOIN service_types st ON vs.service_type_id = st.service_type_id
        WHERE vs.visa_service_id = NEW.visa_service_id;
        
        -- Get user and consultant names
        SELECT CONCAT(first_name, ' ', last_name) INTO v_user_name
        FROM users WHERE id = NEW.user_id;
        
        SELECT CONCAT(first_name, ' ', last_name) INTO v_consultant_name
        FROM users WHERE id = NEW.consultant_id;
        
        -- Handle status changes with appropriate notifications
        CASE v_status_name
            WHEN 'confirmed' THEN
                -- Notify client
                CALL send_notification(
                    'booking_confirmed',
                    NEW.user_id,
                    CONCAT('Your booking for ', v_service_name, ' is confirmed'),
                    CONCAT('Your booking on ', DATE_FORMAT(NEW.booking_datetime, '%Y-%m-%d'), ' at ', 
                           TIME_FORMAT(NEW.booking_datetime, '%H:%i'), ' has been confirmed.'),
                    CONCAT('/dashboard/bookings/', NEW.id),
                    NEW.id, NULL, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.consultant_id,
                    v_notification_id
                );
                
                -- Notify consultant
                CALL send_notification(
                    'booking_confirmed',
                    NEW.consultant_id,
                    CONCAT('Booking with ', v_user_name, ' confirmed'),
                    CONCAT('Your booking with ', v_user_name, ' on ', 
                           DATE_FORMAT(NEW.booking_datetime, '%Y-%m-%d'), ' at ', 
                           TIME_FORMAT(NEW.booking_datetime, '%H:%i'), ' is confirmed.'),
                    CONCAT('/dashboard/consultant/bookings/', NEW.id),
                    NEW.id, NULL, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NULL,
                    v_notification_id
                );
                
            WHEN 'cancelled_by_user' THEN
                -- Notify consultant
                CALL send_notification(
                    'booking_cancelled',
                    NEW.consultant_id,
                    CONCAT('Booking cancelled by ', v_user_name),
                    CONCAT('The booking with ', v_user_name, ' on ', 
                           DATE_FORMAT(NEW.booking_datetime, '%Y-%m-%d'), ' at ', 
                           TIME_FORMAT(NEW.booking_datetime, '%H:%i'), ' has been cancelled by the client.'),
                    CONCAT('/dashboard/consultant/bookings/', NEW.id),
                    NEW.id, NULL, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.user_id,
                    v_notification_id
                );
                
            WHEN 'cancelled_by_consultant' THEN
                -- Notify client
                CALL send_notification(
                    'booking_cancelled',
                    NEW.user_id,
                    CONCAT('Booking cancelled by ', v_consultant_name),
                    CONCAT('Your booking for ', v_service_name, ' on ', 
                           DATE_FORMAT(NEW.booking_datetime, '%Y-%m-%d'), ' at ', 
                           TIME_FORMAT(NEW.booking_datetime, '%H:%i'), ' has been cancelled by the consultant.'),
                    CONCAT('/dashboard/bookings/', NEW.id),
                    NEW.id, NULL, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.consultant_id,
                    v_notification_id
                );
                
            WHEN 'completed' THEN
                -- Notify client to leave feedback
                CALL send_notification(
                    'booking_completed',
                    NEW.user_id,
                    CONCAT('Your booking with ', v_consultant_name, ' is complete'),
                    CONCAT('Your booking for ', v_service_name, ' has been completed. Please take a moment to leave feedback.'),
                    CONCAT('/dashboard/bookings/', NEW.id, '/feedback'),
                    NEW.id, NULL, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.completed_by,
                    v_notification_id
                );
        END CASE;
    END IF;
END //
DELIMITER ;

-- Messages notification trigger
DELIMITER //
CREATE TRIGGER after_message_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    DECLARE v_conversation_type VARCHAR(20);
    DECLARE v_sender_name VARCHAR(255);
    DECLARE v_notification_id INT;
    
    -- Get sender name
    SELECT CONCAT(first_name, ' ', last_name) INTO v_sender_name
    FROM users WHERE id = NEW.user_id;
    
    -- Get conversation type
    SELECT type INTO v_conversation_type
    FROM conversations WHERE id = NEW.conversation_id;
    
    -- If not a system message, notify participants
    IF NEW.is_system_message = 0 THEN
        -- Notify all participants except sender
        INSERT INTO notifications (
            type_id,
            user_id,
            title,
            message,
            action_link,
            related_conversation_id,
            related_message_id,
            organization_id,
            created_by
        )
        SELECT 
            (SELECT id FROM notification_types WHERE type_name = 'message_received'),
            cp.user_id,
            CONCAT('New message from ', v_sender_name),
            LEFT(NEW.message, 100),
            CONCAT('/dashboard/', 
                  CASE 
                      WHEN cp.role = 'consultant' THEN 'consultant/'
                      WHEN cp.role = 'team_member' THEN 'team/'
                      WHEN cp.role = 'applicant' THEN ''
                      ELSE ''
                  END,
                  'messages/', NEW.conversation_id),
            NEW.conversation_id,
            NEW.id,
            (SELECT organization_id FROM conversations WHERE id = NEW.conversation_id),
            NEW.user_id
        FROM conversation_participants cp
        WHERE cp.conversation_id = NEW.conversation_id
        AND cp.user_id != NEW.user_id
        AND cp.left_at IS NULL;
    END IF;
END //
DELIMITER ;

-- Application status update trigger
DELIMITER //
CREATE TRIGGER after_application_status_update
AFTER UPDATE ON applications
FOR EACH ROW
BEGIN
    DECLARE v_status_name VARCHAR(50);
    DECLARE v_visa_type VARCHAR(100);
    DECLARE v_applicant_name VARCHAR(255);
    DECLARE v_consultant_name VARCHAR(255);
    DECLARE v_notification_id INT;
    
    -- Check if status has changed
    IF NEW.status_id != OLD.status_id THEN
        -- Get status name
        SELECT name INTO v_status_name FROM application_statuses WHERE id = NEW.status_id;
        
        -- Get visa type
        SELECT visa_type INTO v_visa_type
        FROM visas WHERE visa_id = NEW.visa_id;
        
        -- Get applicant and consultant names
        SELECT CONCAT(first_name, ' ', last_name) INTO v_applicant_name
        FROM users WHERE id = NEW.user_id;
        
        SELECT CONCAT(first_name, ' ', last_name) INTO v_consultant_name
        FROM users WHERE id = NEW.consultant_id;
        
        -- Notify applicant about status change
        CALL send_notification(
            'application_status_update',
            NEW.user_id,
            CONCAT('Application status updated to: ', v_status_name),
            CONCAT('Your ', v_visa_type, ' application (Ref: ', NEW.reference_number, ') status has been updated to ', v_status_name, '.'),
            CONCAT('/dashboard/applications/', NEW.id),
            NULL, NEW.id, NULL, NULL, NULL, NULL,
            NEW.organization_id,
            NEW.consultant_id,
            v_notification_id
        );
        
        -- For specific status changes, send additional notifications
        CASE v_status_name
            WHEN 'submitted' THEN
                -- Notify consultant
                CALL send_notification(
                    'application_status_update',
                    NEW.consultant_id,
                    CONCAT('Application submitted by ', v_applicant_name),
                    CONCAT(v_applicant_name, '\'s ', v_visa_type, ' application (Ref: ', NEW.reference_number, ') has been submitted.'),
                    CONCAT('/dashboard/consultant/applications/', NEW.id),
                    NULL, NEW.id, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.user_id,
                    v_notification_id
                );
                
            WHEN 'additional_documents_requested' THEN
                -- Notify applicant with higher priority
                CALL send_notification(
                    'document_requested',
                    NEW.user_id,
                    'Additional documents requested',
                    CONCAT('Additional documents have been requested for your ', v_visa_type, ' application (Ref: ', NEW.reference_number, ').'),
                    CONCAT('/dashboard/applications/', NEW.id, '/documents'),
                    NULL, NEW.id, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.consultant_id,
                    v_notification_id
                );
                
            WHEN 'approved' THEN
                -- Notify applicant with congratulatory message
                CALL send_notification(
                    'application_approved',
                    NEW.user_id,
                    'Application Approved!',
                    CONCAT('Congratulations! Your ', v_visa_type, ' application (Ref: ', NEW.reference_number, ') has been approved.'),
                    CONCAT('/dashboard/applications/', NEW.id),
                    NULL, NEW.id, NULL, NULL, NULL, NULL,
                    NEW.organization_id,
                    NEW.consultant_id,
                    v_notification_id
                );
        END CASE;
    END IF;
END //
DELIMITER ;

-- Document notification trigger
DELIMITER //
CREATE TRIGGER after_document_status_update
AFTER UPDATE ON application_documents
FOR EACH ROW
BEGIN
    DECLARE v_document_name VARCHAR(100);
    DECLARE v_application_id INT;
    DECLARE v_applicant_id INT;
    DECLARE v_consultant_id INT;
    DECLARE v_notification_id INT;
    
    -- Check if status has changed
    IF NEW.status != OLD.status THEN
        -- Get document name
        SELECT name INTO v_document_name
        FROM document_types WHERE id = NEW.document_type_id;
        
        -- Get application details
        SELECT a.id, a.user_id, a.consultant_id 
        INTO v_application_id, v_applicant_id, v_consultant_id
        FROM applications a
        WHERE a.id = NEW.application_id;
        
        -- Handle document status changes
        CASE NEW.status
            WHEN 'approved' THEN
                -- Notify applicant
                CALL send_notification(
                    'document_approved',
                    v_applicant_id,
                    CONCAT(v_document_name, ' approved'),
                    CONCAT('Your ', v_document_name, ' has been approved.'),
                    CONCAT('/dashboard/applications/', v_application_id, '/documents'),
                    NULL, v_application_id, NULL, NULL, NEW.id, NULL,
                    NEW.organization_id,
                    NEW.reviewed_by,
                    v_notification_id
                );
                
            WHEN 'rejected' THEN
                -- Notify applicant
                CALL send_notification(
                    'document_rejected',
                    v_applicant_id,
                    CONCAT(v_document_name, ' rejected'),
                    CONCAT('Your ', v_document_name, ' has been rejected. Reason: ', IFNULL(NEW.rejection_reason, 'No reason provided')),
                    CONCAT('/dashboard/applications/', v_application_id, '/documents'),
                    NULL, v_application_id, NULL, NULL, NEW.id, NULL,
                    NEW.organization_id,
                    NEW.reviewed_by,
                    v_notification_id
                );
                
            WHEN 'submitted' THEN
                -- Notify consultant
                CALL send_notification(
                    'document_uploaded',
                    v_consultant_id,
                    CONCAT('New document submitted: ', v_document_name),
                    CONCAT('A new ', v_document_name, ' has been submitted and is waiting for review.'),
                    CONCAT('/dashboard/consultant/applications/', v_application_id, '/documents'),
                    NULL, v_application_id, NULL, NULL, NEW.id, NULL,
                    NEW.organization_id,
                    NEW.submitted_by,
                    v_notification_id
                );
        END CASE;
    END IF;
END //
DELIMITER ;

-- AI Chat notification
DELIMITER //
CREATE TRIGGER after_ai_chat_message_insert
AFTER INSERT ON ai_chat_messages
FOR EACH ROW
BEGIN
    DECLARE v_notification_id INT;
    
    -- Only notify for AI assistant responses
    IF NEW.role = 'assistant' THEN
        -- Notify user of AI response
        CALL send_notification(
            'ai_chat_response',
            NEW.consultant_id,
            'New AI response in your chat',
            LEFT(NEW.content, 100),
            CONCAT('/dashboard/consultant/ai-chat?conversation_id=', NEW.conversation_id),
            NULL, NULL, NULL, NULL, NULL, NEW.conversation_id,
            (SELECT organization_id FROM users WHERE id = NEW.consultant_id),
            NULL,
            v_notification_id
        );
    END IF;
END //
DELIMITER ;

-- Create stored procedure to mark notifications as read
DELIMITER //
CREATE PROCEDURE mark_notification_read(
    IN p_notification_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE notifications
    SET is_read = 1, read_at = NOW()
    WHERE id = p_notification_id AND user_id = p_user_id;
END //
DELIMITER ;

-- Create stored procedure to mark all notifications as read for a user
DELIMITER //
CREATE PROCEDURE mark_all_notifications_read(
    IN p_user_id INT
)
BEGIN
    UPDATE notifications
    SET is_read = 1, read_at = NOW()
    WHERE user_id = p_user_id AND is_read = 0;
END //
DELIMITER ;

-- Create stored procedure to dismiss a notification
DELIMITER //
CREATE PROCEDURE dismiss_notification(
    IN p_notification_id INT,
    IN p_user_id INT
)
BEGIN
    UPDATE notifications
    SET is_dismissed = 1, dismissed_at = NOW()
    WHERE id = p_notification_id AND user_id = p_user_id;
END //
DELIMITER ;

-- Create view for user's unread notifications
CREATE OR REPLACE VIEW user_unread_notifications_view AS
SELECT 
    n.id,
    n.type_id,
    nt.type_name,
    nt.icon,
    nt.color,
    n.user_id,
    CONCAT(u.first_name, ' ', u.last_name) AS user_name,
    n.title,
    n.message,
    n.action_link,
    n.related_booking_id,
    n.related_application_id,
    n.related_conversation_id,
    n.related_message_id,
    n.related_document_id,
    n.related_ai_chat_id,
    n.organization_id,
    o.name AS organization_name,
    n.created_at,
    TIMESTAMPDIFF(SECOND, n.created_at, NOW()) AS seconds_ago
FROM 
    notifications n
JOIN 
    notification_types nt ON n.type_id = nt.id
JOIN 
    users u ON n.user_id = u.id
LEFT JOIN 
    organizations o ON n.organization_id = o.id
WHERE 
    n.is_read = 0
    AND n.is_dismissed = 0
    AND (n.expires_at IS NULL OR n.expires_at > NOW())
ORDER BY 
    n.created_at DESC;

-- Create view for notification statistics
CREATE OR REPLACE VIEW notification_statistics_view AS
SELECT 
    o.id AS organization_id,
    o.name AS organization_name,
    nt.id AS notification_type_id,
    nt.type_name,
    COUNT(n.id) AS total_count,
    SUM(CASE WHEN n.is_read = 1 THEN 1 ELSE 0 END) AS read_count,
    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
    SUM(CASE WHEN n.is_dismissed = 1 THEN 1 ELSE 0 END) AS dismissed_count,
    COUNT(DISTINCT n.user_id) AS affected_users_count,
    MAX(n.created_at) AS latest_notification_date
FROM 
    organizations o
LEFT JOIN 
    notifications n ON o.id = n.organization_id
LEFT JOIN 
    notification_types nt ON n.type_id = nt.id
GROUP BY 
    o.id, o.name, nt.id, nt.type_name;

-- Create job to clean up old dismissed notifications (keeps database size manageable)
DELIMITER //
CREATE EVENT IF NOT EXISTS clean_old_notifications
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Delete dismissed notifications older than 90 days
    DELETE FROM notifications 
    WHERE is_dismissed = 1 
    AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Delete read notifications older than 180 days
    DELETE FROM notifications 
    WHERE is_read = 1 
    AND is_dismissed = 0
    AND created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
    
    -- Delete expired notifications
    DELETE FROM notifications
    WHERE expires_at IS NOT NULL
    AND expires_at < NOW();
END //
DELIMITER ;

-- Create function to get unread notification count for a user
DELIMITER //
CREATE FUNCTION get_unread_notification_count(p_user_id INT) 
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    
    SELECT COUNT(*) INTO v_count
    FROM notifications
    WHERE user_id = p_user_id
    AND is_read = 0
    AND is_dismissed = 0
    AND (expires_at IS NULL OR expires_at > NOW());
    
    RETURN v_count;
END //
DELIMITER ;

-- Create scheduled event to send booking reminders
DELIMITER //
CREATE EVENT IF NOT EXISTS send_booking_reminders
ON SCHEDULE EVERY 30 MINUTE
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DECLARE v_booking_id INT;
    DECLARE v_user_id INT;
    DECLARE v_consultant_id INT;
    DECLARE v_booking_datetime DATETIME;
    DECLARE v_service_name VARCHAR(255);
    DECLARE v_organization_id INT;
    DECLARE v_notification_id INT;
    DECLARE done INT DEFAULT FALSE;
    
    -- Cursor for upcoming bookings needing reminders (between 24-25 hours in advance)
    DECLARE cur CURSOR FOR 
        SELECT b.id, b.user_id, b.consultant_id, b.booking_datetime, st.service_name, b.organization_id
        FROM bookings b
        JOIN booking_statuses bs ON b.status_id = bs.id
        JOIN visa_services vs ON b.visa_service_id = vs.visa_service_id
        JOIN service_types st ON vs.service_type_id = st.service_type_id
        WHERE bs.name = 'confirmed'
        AND b.deleted_at IS NULL
        AND b.reminded_at IS NULL
        AND b.booking_datetime BETWEEN NOW() + INTERVAL 23 HOUR AND NOW() + INTERVAL 25 HOUR;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_booking_id, v_user_id, v_consultant_id, v_booking_datetime, v_service_name, v_organization_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Send reminder to client
        CALL send_notification(
            'booking_reminder',
            v_user_id,
            CONCAT('Reminder: ', v_service_name, ' tomorrow'),
            CONCAT('This is a reminder of your booking for ', v_service_name, ' tomorrow at ', 
                   TIME_FORMAT(v_booking_datetime, '%H:%i'), '.'),
            CONCAT('/dashboard/bookings/', v_booking_id),
            v_booking_id, NULL, NULL, NULL, NULL, NULL,
            v_organization_id,
            NULL,
            v_notification_id
        );
        
        -- Send reminder to consultant
        CALL send_notification(
            'booking_reminder',
            v_consultant_id,
            'Booking reminder for tomorrow',
            CONCAT('This is a reminder that you have a booking for ', v_service_name, ' tomorrow at ', 
                   TIME_FORMAT(v_booking_datetime, '%H:%i'), '.'),
            CONCAT('/dashboard/consultant/bookings/', v_booking_id),
            v_booking_id, NULL, NULL, NULL, NULL, NULL,
            v_organization_id,
            NULL,
            v_notification_id
        );
        
        -- Update booking to record that reminder was sent
        UPDATE bookings SET reminded_at = NOW() WHERE id = v_booking_id;
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- Create scheduled event to automatically expire notifications
DELIMITER //
CREATE EVENT IF NOT EXISTS expire_old_notifications
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- For notifications older than 30 days, set expires_at if not already set
    UPDATE notifications
    SET expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY)
    WHERE expires_at IS NULL
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //
DELIMITER ;

-- Set up default notification settings for new users
DELIMITER //
CREATE TRIGGER after_user_insert 
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    -- Insert default notification settings for each notification type
    INSERT INTO notification_settings (user_id, type_id, email_enabled, push_enabled, sms_enabled, in_app_enabled)
    SELECT NEW.id, id, 1, 1, 0, 1
    FROM notification_types;
    
    -- Set up email channel
    IF NEW.email IS NOT NULL AND NEW.email != '' THEN
        INSERT INTO notification_channels (user_id, channel_type, channel_value, is_verified, is_primary)
        VALUES (NEW.id, 'email', NEW.email, NEW.email_verified, 1);
    END IF;
    
    -- Set up SMS channel if phone exists
    IF NEW.phone IS NOT NULL AND NEW.phone != '' THEN
        INSERT INTO notification_channels (user_id, channel_type, channel_value, is_verified, is_primary)
        VALUES (NEW.id, 'sms', NEW.phone, 0, 1);
    END IF;
    
    -- Send welcome notification
    INSERT INTO notifications (
        type_id,
        user_id,
        title,
        message,
        action_link,
        organization_id
    ) VALUES (
        (SELECT id FROM notification_types WHERE type_name = 'reminder'),
        NEW.id,
        'Welcome to the platform',
        'Thank you for joining! Complete your profile to get started.',
        '/dashboard/profile',
        NEW.organization_id
    );
END //
DELIMITER ;

-- Create API for push notification tokens
CREATE TABLE `push_notification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_device_token` (`user_id`, `device_token`),
  KEY `idx_push_notification_tokens_active` (`is_active`),
  CONSTRAINT `push_notification_tokens_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create push notification logs
CREATE TABLE `push_notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `token_id` int(11) NOT NULL,
  `payload` text NOT NULL,
  `status` enum('pending','sent','delivered','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `token_id` (`token_id`),
  KEY `idx_push_notification_logs_status` (`status`),
  CONSTRAINT `push_notification_logs_notification_id_fk` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `push_notification_logs_token_id_fk` FOREIGN KEY (`token_id`) REFERENCES `push_notification_tokens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
