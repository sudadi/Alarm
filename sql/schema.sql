CREATE DATABASE IF NOT EXISTS alarmdb CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
USE alarmdb;

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('codered','codeblue','admin') NOT NULL DEFAULT 'codered',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_idx` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `btn_devices` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `btn_code` varchar(10) NOT NULL,
  `location` varchar(100) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `last_ping` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Disconnect','Connected') NOT NULL DEFAULT 'Disconnect',
  PRIMARY KEY (`id`),
  UNIQUE KEY `btn_devices_ip_IDX` (`ip`),
  UNIQUE KEY `btn_devices_btn_code_IDX` (`btn_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `btn_state` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `btn_code` varchar(10) NOT NULL,
  `btn_type` enum('Red','Blue') NOT NULL,
  `state` enum('OFF','ON') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `btn_red_btn_devices_FK` (`btn_code`),
  CONSTRAINT `btn_red_btn_devices_FK` FOREIGN KEY (`btn_code`) REFERENCES `btn_devices` (`btn_code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `btn_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `btn_code` varchar(10) NOT NULL,
  `btn_type` enum('red','blue') NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alarm_log_btn_devices_FK` (`btn_code`),
  CONSTRAINT `alarm_log_btn_devices_FK` FOREIGN KEY (`btn_code`) REFERENCES `btn_devices` (`btn_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `is_active`)
SELECT 'admin', '$2y$12$d8VBgo4X7lnlZu5tgE31q.yszXofoAvdEztFyjFg2EoOqKUigsyLy', 'Administrator', 'admin', 1
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');
