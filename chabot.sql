-- AI Chat Conversations table
CREATE TABLE IF NOT EXISTS `ai_chat_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultant_id` int(11) NOT NULL COMMENT 'References consultants.user_id',
  `title` varchar(255) NOT NULL,
  `chat_type` enum('ircc', 'cases') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultant_id` (`consultant_id`),
  CONSTRAINT `ai_chat_conversations_consultant_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- AI Chat Messages table
CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `consultant_id` int(11) NOT NULL COMMENT 'References consultants.user_id',
  `role` enum('user', 'assistant') NOT NULL,
  `content` text NOT NULL,
  `tokens` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `consultant_id` (`consultant_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `ai_chat_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `ai_chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_chat_messages_consultant_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- AI Chat Usage tracking table
CREATE TABLE IF NOT EXISTS `ai_chat_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultant_id` int(11) NOT NULL COMMENT 'References consultants.user_id',
  `month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `message_count` int(11) NOT NULL DEFAULT 0,
  `token_count` int(11) NOT NULL DEFAULT 0,
  `chat_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of chats initiated this month',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `consultant_month` (`consultant_id`, `month`),
  CONSTRAINT `ai_chat_usage_consultant_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 


-- AI Chat Conversations table
CREATE TABLE IF NOT EXISTS `ai_chat_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultant_id` int(11) NOT NULL COMMENT 'References consultants.user_id',
  `title` varchar(255) NOT NULL,
  `chat_type` enum('ircc', 'cases') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultant_id` (`consultant_id`),
  CONSTRAINT `ai_chat_conversations_consultant_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- AI Chat Messages table
CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `consultant_id` int(11) NOT NULL COMMENT 'References consultants.user_id',
  `role` enum('user', 'assistant') NOT NULL,
  `content` text NOT NULL,
  `tokens` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `consultant_id` (`consultant_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `ai_chat_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `ai_chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_chat_messages_consultant_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- AI Chat Usage tracking table
CREATE TABLE IF NOT EXISTS `ai_chat_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultant_id` int(11) NOT NULL COMMENT 'References consultants.user_id',
  `month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `message_count` int(11) NOT NULL DEFAULT 0,
  `token_count` int(11) NOT NULL DEFAULT 0,
  `chat_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of chats initiated this month',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `consultant_month` (`consultant_id`, `month`),
  CONSTRAINT `ai_chat_usage_consultant_fk` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;