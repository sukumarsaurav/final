-- Main tasks table (updated for organization structure)
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `creator_id` int(11) NOT NULL COMMENT 'The user who created the task (can be consultant or team member)',
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `organization_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_priority` (`priority`),
  KEY `idx_tasks_due_date` (`due_date`),
  KEY `idx_tasks_organization` (`organization_id`),
  CONSTRAINT `tasks_creator_id_fk` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_organization_id_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Task assignments table for multiple assignees
CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `assignee_id` int(11) NOT NULL COMMENT 'The user assigned to the task (can be consultant or team member)',
  `assigned_by` int(11) NOT NULL COMMENT 'The user who made the assignment',
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_assignee` (`task_id`, `assignee_id`),
  KEY `assignee_id` (`assignee_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_task_assignments_status` (`status`),
  CONSTRAINT `task_assignments_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_assignments_assignee_id_fk` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_assignments_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Task Comments table (updated)
CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Can be consultant or team member',
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_comments_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Task attachments table (updated)
CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Who uploaded the attachment',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL COMMENT 'Size in bytes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_attachments_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Task activity log (updated for organization structure)
CREATE TABLE `task_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'User who performed the action',
  `affected_user_id` int(11) DEFAULT NULL COMMENT 'The user being acted upon, if applicable',
  `activity_type` enum('created','updated','status_changed','assigned','unassigned','assignee_status_changed','commented','attachment_added') NOT NULL,
  `description` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  KEY `affected_user_id` (`affected_user_id`),
  KEY `idx_task_activity_type` (`activity_type`),
  CONSTRAINT `task_activity_logs_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_activity_logs_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_activity_logs_affected_user_id_fk` FOREIGN KEY (`affected_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- View to show tasks with their creators and organization info
CREATE VIEW `organization_tasks_view` AS
SELECT 
    t.id,
    t.name,
    t.description,
    t.priority,
    t.status,
    t.due_date,
    t.completed_at,
    t.created_at,
    o.name AS organization_name,
    CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
    u.user_type AS creator_type
FROM 
    tasks t
    JOIN organizations o ON t.organization_id = o.id
    JOIN users u ON t.creator_id = u.id
WHERE
    t.deleted_at IS NULL;

-- View to show task assignments with assignee details
CREATE VIEW `task_assignments_view` AS
SELECT 
    ta.id,
    ta.task_id,
    t.name AS task_name,
    ta.status AS assignment_status,
    CONCAT(assignee.first_name, ' ', assignee.last_name) AS assignee_name,
    assignee.user_type AS assignee_type,
    CONCAT(assigner.first_name, ' ', assigner.last_name) AS assigned_by_name,
    assigner.user_type AS assigned_by_type,
    ta.started_at,
    ta.completed_at,
    ta.created_at AS assigned_at
FROM 
    task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    JOIN users assignee ON ta.assignee_id = assignee.id
    JOIN users assigner ON ta.assigned_by = assigner.id
WHERE
    ta.deleted_at IS NULL AND t.deleted_at IS NULL;

-- Procedure to assign task to user (verifying they're in the same organization)
DELIMITER //
CREATE PROCEDURE `assign_task_to_user`(
    IN p_task_id INT,
    IN p_assignee_id INT,
    IN p_assigned_by INT
)
BEGIN
    DECLARE v_task_org_id INT;
    DECLARE v_assignee_org_id INT;
    DECLARE v_assigner_org_id INT;
    
    -- Get organization IDs
    SELECT organization_id INTO v_task_org_id FROM tasks WHERE id = p_task_id;
    SELECT organization_id INTO v_assignee_org_id FROM users WHERE id = p_assignee_id;
    SELECT organization_id INTO v_assigner_org_id FROM users WHERE id = p_assigned_by;
    
    -- Check if all belong to the same organization
    IF v_task_org_id = v_assignee_org_id AND v_task_org_id = v_assigner_org_id THEN
        -- Insert the assignment
        INSERT INTO task_assignments (task_id, assignee_id, assigned_by, status)
        VALUES (p_task_id, p_assignee_id, p_assigned_by, 'pending');
        
        -- Log the activity
        INSERT INTO task_activity_logs (task_id, user_id, affected_user_id, activity_type, description)
        VALUES (
            p_task_id, 
            p_assigned_by, 
            p_assignee_id, 
            'assigned', 
            CONCAT('Task assigned to user ID ', p_assignee_id)
        );
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Cannot assign task: users must belong to the same organization as the task';
    END IF;
END //
DELIMITER ;

-- Trigger to update task status when all assignments are completed
DELIMITER //
CREATE TRIGGER update_task_status_after_assignment_update
AFTER UPDATE ON task_assignments
FOR EACH ROW
BEGIN
    DECLARE all_completed BOOLEAN;
    DECLARE task_has_assignments BOOLEAN;
    
    -- Check if the task has any assignments
    SELECT COUNT(*) > 0 INTO task_has_assignments
    FROM task_assignments
    WHERE task_id = NEW.task_id AND deleted_at IS NULL;
    
    -- Check if all assignments for this task are completed
    SELECT COUNT(*) = 0 INTO all_completed
    FROM task_assignments
    WHERE task_id = NEW.task_id 
      AND status != 'completed' 
      AND deleted_at IS NULL;
    
    -- If all assignments are completed, update the task status
    IF task_has_assignments AND all_completed THEN
        UPDATE tasks 
        SET status = 'completed', completed_at = NOW() 
        WHERE id = NEW.task_id;
    END IF;
END //
DELIMITER ;