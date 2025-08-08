-- Create the missing whatsapp_messages table to fix logging errors
CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
    `id` varchar(255) NOT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `direction` varchar(50) NOT NULL,
    `recipient` varchar(255) NOT NULL,
    `message_type` varchar(50) NOT NULL,
    `payload` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `whatsapp_messages_session_id_index` (`session_id`),
    KEY `whatsapp_messages_direction_index` (`direction`),
    KEY `whatsapp_messages_recipient_index` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;