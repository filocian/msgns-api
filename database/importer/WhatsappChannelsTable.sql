CREATE TABLE `whatsapp_channels`
(
	`nfc_id`              BIGINT NULL,
	`lang_id`             BIGINT NULL,
	`mobile_prefix`       BIGINT NULL,
	`mobile`              VARCHAR(2048) NULL,
	`text`                VARCHAR(2048) NULL,
	`fecha_hora`          DATETIME NULL,
	`default_translation` BIGINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;