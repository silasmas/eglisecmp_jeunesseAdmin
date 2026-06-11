-- Production : créer la table des alertes d'échec de paiement (Hostinger / phpMyAdmin)
-- Base : u911414181_jeunesse (ajuster si besoin)

CREATE TABLE IF NOT EXISTS `retreat_payment_failure_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `retreat_payment_id` bigint unsigned DEFAULT NULL,
  `participant_id` bigint unsigned DEFAULT NULL,
  `event_id` bigint unsigned DEFAULT NULL,
  `reference` varchar(64) NOT NULL,
  `channel` varchar(32) DEFAULT NULL,
  `failure_reason` varchar(64) NOT NULL,
  `failure_source` varchar(64) NOT NULL,
  `message` text NOT NULL,
  `technical_detail` json DEFAULT NULL,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `email_recipient` varchar(255) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_payment_failure_alerts_reference_index` (`reference`),
  KEY `retreat_payment_failure_alerts_acknowledged_at_created_at_index` (`acknowledged_at`, `created_at`),
  KEY `retreat_payment_failure_alerts_retreat_payment_id_foreign` (`retreat_payment_id`),
  KEY `retreat_payment_failure_alerts_participant_id_foreign` (`participant_id`),
  KEY `retreat_payment_failure_alerts_event_id_foreign` (`event_id`),
  KEY `retreat_payment_failure_alerts_acknowledged_by_foreign` (`acknowledged_by`),
  CONSTRAINT `retreat_payment_failure_alerts_retreat_payment_id_foreign` FOREIGN KEY (`retreat_payment_id`) REFERENCES `retreat_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `retreat_payment_failure_alerts_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `retreat_participant` (`id`) ON DELETE SET NULL,
  CONSTRAINT `retreat_payment_failure_alerts_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`) ON DELETE SET NULL,
  CONSTRAINT `retreat_payment_failure_alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enregistrer la migration Laravel (optionnel, si vous utilisez php artisan migrate ensuite)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_09_130000_create_retreat_payment_failure_alerts_table', IFNULL(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_09_130000_create_retreat_payment_failure_alerts_table'
);
