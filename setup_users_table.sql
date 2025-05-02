-- SQL script to create the users table

-- Check if users table exists, if not create it
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin user if the table is empty
-- Password hash for 'admin123' using PHP's password_hash with PASSWORD_DEFAULT
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`)
SELECT 'admin', 'admin@example.com', '$2y$10$YOURSALTOFABOUThhVdJyO1K22OZgpzK.nOWHw9o6NL5FrOvYZNw2', 'admin', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin'); 