CREATE TABLE `new_products` (
							`id` bigint(20) UNSIGNED NOT NULL,
							`product_type_id` bigint(20) UNSIGNED NOT NULL,
							`user_id` bigint(20) UNSIGNED DEFAULT NULL,
							`config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
							`target_url` text DEFAULT NULL,
							`password` varchar(255) NOT NULL,
							`usage` int(11) DEFAULT NULL,
							`name` varchar(150) NOT NULL DEFAULT '',
							`description` text DEFAULT NULL,
							`active` tinyint(1) NOT NULL DEFAULT 0,
							`tags` varchar(255) DEFAULT NULL,
							`admin_tags` varchar(255) DEFAULT NULL,
							`created_at` timestamp NULL DEFAULT NULL,
							`updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;