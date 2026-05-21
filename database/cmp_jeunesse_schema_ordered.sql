-- --------------------------------------------------------
-- Hôte:                         127.0.0.1
-- Version du serveur:           8.4.3 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.8.0.6908
-- Ordre d'exécution : parent → enfant (selon FK)
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Base
CREATE DATABASE IF NOT EXISTS `cmp_jeunesse` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `cmp_jeunesse`;

-- =============================================================================
-- NIVEAU 0 : tables sans clé étrangère (ou références logiques non contraintes)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `api_docs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `data` json DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_docs_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_tags_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_operators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'keccel',
  `send_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POST',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `remaining_sms` int unsigned DEFAULT NULL,
  `last_balance_checked_at` timestamp NULL DEFAULT NULL,
  `last_balance_response` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_operators_provider_is_active_index` (`provider`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_testimony` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant temoignage',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom affiche auteur',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contenu temoignage',
  `color` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Couleur d affichage UI',
  `date_submitted` datetime NOT NULL COMMENT 'Date soumission',
  `validated` tinyint(1) NOT NULL COMMENT 'Temoignage valide/modere',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Temoignage visible ou masque',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 1 : auto-référence ou pivots Spatie (parents des permissions)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postnom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sexe` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `role_participant` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `indicatif_telephone` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_urgence` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commune` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eglise_assemblee` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement_cellule` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hebergement_choice` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observation` text COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL COMMENT 'Date de derniere connexion',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Compte utilisateur actif/inactif',
  `fonction_metier` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fonction metier libelle (distinct des roles Shield/Spatie)',
  `role_jeunesse` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `profile_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_owner_id_index` (`owner_id`),
  KEY `users_fonction_metier_index` (`fonction_metier`),
  CONSTRAINT `users_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_folders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_folders_parent_id_foreign` (`parent_id`),
  CONSTRAINT `media_folders_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 2 : médias (users + media_folders)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `media_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uploaded_by_user_id` bigint unsigned DEFAULT NULL,
  `folder_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned DEFAULT NULL,
  `extension` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int unsigned DEFAULT NULL,
  `height` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_files_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  KEY `media_files_folder_id_foreign` (`folder_id`),
  CONSTRAINT `media_files_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_files_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `media_file_id` bigint unsigned NOT NULL,
  `attachable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` bigint unsigned NOT NULL,
  `collection` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_attachments_media_file_id_foreign` (`media_file_id`),
  KEY `media_attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`),
  CONSTRAINT `media_attachments_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_taggables` (
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint unsigned NOT NULL,
  KEY `media_taggables_tag_id_foreign` (`tag_id`),
  KEY `media_taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `media_taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `media_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 3 : événements (media_files)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `events_event` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de l evenement',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de l evenement',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d evenement: retraite, culte, conference...',
  `start_at` datetime DEFAULT NULL COMMENT 'Date/heure de debut',
  `end_at` datetime DEFAULT NULL COMMENT 'Date/heure de fin',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Lieu principal de l evenement',
  `affiche` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiche_id` bigint unsigned DEFAULT NULL,
  `capacity` int unsigned DEFAULT NULL COMMENT 'Capacite maximale autorisee',
  `price_to_pay` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Montant a payer pour participer a cet evenement',
  `currency` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD' COMMENT 'Monnaie de reference pour le paiement',
  `access_auth_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'password' COMMENT 'Connexion utilisateurs: password ou otp',
  `access_otp_channel` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Si otp: sms ou email',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Evenement ouvert (actif) ou ferme',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_event_start_at_idx` (`start_at`),
  KEY `events_event_type_is_active_idx` (`type`,`is_active`),
  KEY `events_event_type_index` (`type`),
  KEY `events_event_affiche_id_foreign` (`affiche_id`),
  CONSTRAINT `events_event_affiche_id_foreign` FOREIGN KEY (`affiche_id`) REFERENCES `media_files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 4 : retraite — structure liée à users / events_event
-- =============================================================================

CREATE TABLE IF NOT EXISTS `retreat_atelier` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant atelier',
  `responsable_user_id` bigint unsigned DEFAULT NULL,
  `adjoint_user_id` bigint unsigned DEFAULT NULL,
  `numero` int unsigned NOT NULL COMMENT 'Numero datelier',
  `role_on_atelier` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'responsable' COMMENT 'Role operationnel du responsable atelier',
  `description` text COLLATE utf8mb4_unicode_ci,
  `rapport_final` longtext COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Atelier ouvert aux affectations ou ferme',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_atelier_numero_responsable_unique` (`numero`,`responsable_user_id`),
  KEY `retreat_atelier_responsable_user_id_index` (`responsable_user_id`),
  KEY `retreat_atelier_adjoint_user_id_index` (`adjoint_user_id`),
  CONSTRAINT `retreat_atelier_adjoint_user_id_foreign` FOREIGN KEY (`adjoint_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `retreat_atelier_responsable_user_id_foreign` FOREIGN KEY (`responsable_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_chambre` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant chambre',
  `nom` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom/code court de chambre',
  `capacite` int unsigned NOT NULL COMMENT 'Capacite maximale',
  `sexe` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sexe autorise (homme/femme/mixte)',
  `responsable_user_id` bigint unsigned DEFAULT NULL,
  `role_on_chambre` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'responsable' COMMENT 'Role operationnel du responsable chambre',
  `description` text COLLATE utf8mb4_unicode_ci,
  `rapport_final` longtext COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Chambre ouverte aux affectations ou fermee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_chambre_nom_sexe_responsable_unique` (`nom`,`sexe`,`responsable_user_id`),
  KEY `retreat_chambre_responsable_user_id_index` (`responsable_user_id`),
  CONSTRAINT `retreat_chambre_responsable_user_id_foreign` FOREIGN KEY (`responsable_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_session` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant session',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titre session',
  `start_at` datetime NOT NULL COMMENT 'Debut session',
  `end_at` datetime NOT NULL COMMENT 'Fin session',
  `room` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Salle/zone session',
  `event_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Session ouverte au planning ou annulee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_session_event_start_idx` (`event_id`,`start_at`),
  KEY `retreat_session_event_id_index` (`event_id`),
  CONSTRAINT `retreat_session_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_retreatdetail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant detail retraite',
  `theme` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Theme principal',
  `speaker` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Intervenant principal',
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Notes generales',
  `event_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Bloc detail retraite actif ou archive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_retreatdetail_event_id_unique` (`event_id`),
  CONSTRAINT `retreat_retreatdetail_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de la politique/reglement',
  `event_id` bigint unsigned DEFAULT NULL,
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Categorie: reglement, condition, sanction',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titre de la regle',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contenu detaille a respecter',
  `target_audience` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all' COMMENT 'Public cible: all, participant, worker, speaker',
  `severity_level` int unsigned NOT NULL DEFAULT '1' COMMENT 'Niveau de severite (1-5)',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Lecture/acceptation obligatoire',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Regle active ou archivee',
  `effective_from` datetime DEFAULT NULL COMMENT 'Date debut application',
  `effective_to` datetime DEFAULT NULL COMMENT 'Date fin application',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_policies_created_by_foreign` (`created_by`),
  KEY `retreat_policies_event_category_idx` (`event_id`,`category`),
  KEY `retreat_policies_audience_active_idx` (`target_audience`,`is_active`),
  CONSTRAINT `retreat_policies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `retreat_policies_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_notification` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant notification',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Titre court pour la liste',
  `message` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Message notifie',
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info' COMMENT 'info, success, warning, payment, participant',
  `link` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lien cible',
  `is_read` tinyint(1) NOT NULL COMMENT 'Notification lue ou non',
  `user_id` bigint unsigned DEFAULT NULL,
  `laravel_notification_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID lie a notifications.id si duplique',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Notification active ou archivee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_notification_user_id_index` (`user_id`),
  KEY `retreat_notification_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `retreat_notification_category_index` (`category`),
  CONSTRAINT `retreat_notification_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 5 : participants (events + atelier + chambre + users)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `retreat_participant` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant participant',
  `event_id` bigint unsigned DEFAULT NULL,
  `family_group_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Regroupe les membres d’un même foyer (liaison tél. urgence / portable)',
  `family_group_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `family_contact_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom participant',
  `postnom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Prenom participant',
  `date_naissance` date DEFAULT NULL,
  `age` int unsigned NOT NULL COMMENT 'Age participant',
  `preuve_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Justificatif paiement',
  `paiement_valide` tinyint(1) NOT NULL COMMENT 'Paiement valide oui/non',
  `atelier_id` bigint unsigned DEFAULT NULL,
  `chambre_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email participant',
  `sexe` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sexe participant',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telephone principal',
  `indicatif_telephone` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'QR code de billet/acces',
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse physique',
  `commune` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observation` text COLLATE utf8mb4_unicode_ci COMMENT 'Observations generales',
  `eglise_assemblee` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement_cellule` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hebergement_choice` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_urgence` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Contact urgence',
  `date_presence` datetime DEFAULT NULL COMMENT 'Premiere date de presence',
  `present` tinyint(1) NOT NULL COMMENT 'Presence globale',
  `owner_id` bigint unsigned DEFAULT NULL,
  `billet_envoye` tinyint(1) NOT NULL COMMENT 'Billet deja envoye',
  `date_billet_envoye` datetime DEFAULT NULL COMMENT 'Date envoi billet',
  `billet_pdf` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fichier PDF billet',
  `download_token` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token unique de telechargement billet',
  `role_participant` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Role du participant dans la retraite',
  `participant_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal' COMMENT 'Type de participant: internal ou external',
  `exit_allowed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Autorisation de sortie hors site de retraite',
  `curfew_time` time DEFAULT NULL COMMENT 'Heure limite de retour si sortie autorisee',
  `guardian_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom du parent/tuteur pour suivi securite',
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telephone du parent/tuteur',
  `registration_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Etat inscription: pending, otp_sent, otp_verified, completed',
  `registration_otp_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code OTP inscription en cours',
  `registration_otp_sent_at` datetime DEFAULT NULL COMMENT 'Date denvoi OTP',
  `registration_otp_expires_at` datetime DEFAULT NULL COMMENT 'Date expiration OTP',
  `registration_otp_verified_at` datetime DEFAULT NULL COMMENT 'Date verification OTP',
  `registration_otp_attempts` int unsigned NOT NULL DEFAULT '0' COMMENT 'Nombre de tentatives OTP',
  `photo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo profil participant',
  `is_verified` tinyint(1) NOT NULL COMMENT 'Profil verifie',
  `billet_envoye_email` tinyint(1) NOT NULL COMMENT 'Billet envoye par email',
  `billet_envoye_whatsapp` tinyint(1) NOT NULL COMMENT 'Billet envoye par WhatsApp',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Fiche participant ouverte (inscription/edition) ou fermee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_participant_download_token_unique` (`download_token`),
  UNIQUE KEY `retreat_participant_identity_unique` (`nom`,`postnom`,`prenom`),
  UNIQUE KEY `retreat_participant_event_nom_prenom_postnom_unique` (`event_id`,`nom`,`prenom`,`postnom`),
  KEY `retreat_participant_atelier_id_index` (`atelier_id`),
  KEY `retreat_participant_chambre_id_index` (`chambre_id`),
  KEY `retreat_participant_user_id_index` (`user_id`),
  KEY `retreat_participant_owner_id_index` (`owner_id`),
  KEY `retreat_participant_participant_type_index` (`participant_type`),
  KEY `retreat_participant_exit_allowed_index` (`exit_allowed`),
  KEY `retreat_participant_registration_status_index` (`registration_status`),
  KEY `retreat_participant_event_reg_status_idx` (`event_id`,`registration_status`),
  KEY `retreat_participant_family_group_id_idx` (`family_group_id`),
  KEY `retreat_participant_family_contact_hash_idx` (`family_contact_hash`),
  CONSTRAINT `retreat_participant_atelier_id_foreign` FOREIGN KEY (`atelier_id`) REFERENCES `retreat_atelier` (`id`),
  CONSTRAINT `retreat_participant_chambre_id_foreign` FOREIGN KEY (`chambre_id`) REFERENCES `retreat_chambre` (`id`),
  CONSTRAINT `retreat_participant_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`) ON DELETE SET NULL,
  CONSTRAINT `retreat_participant_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `retreat_participant_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 6 : activités, paiements, politiques (enfants de participant / session)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `retreat_activity_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de l activite',
  `session_id` bigint unsigned DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titre de l activite',
  `activity_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type: enseignement, priere, atelier, service, etc.',
  `location` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lieu/salle de l activite',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Presence obligatoire ou non',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned' COMMENT 'Etat: planned, ongoing, done, cancelled',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Consignes ou details',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Activite planifiee active ou annulee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `starts_at` time NOT NULL,
  `ends_at` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_activity_plans_session_id_index` (`session_id`),
  KEY `retreat_activity_plans_type_status_idx` (`activity_type`,`status`),
  KEY `retreat_activity_plans_session_starts_idx` (`session_id`,`starts_at`),
  CONSTRAINT `retreat_activity_plans_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `retreat_session` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique du paiement',
  `participant_id` bigint unsigned NOT NULL,
  `event_id` bigint unsigned NOT NULL,
  `reference` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Reference interne unique (equivalent reference FlexPay)',
  `amount_expected` decimal(12,2) NOT NULL COMMENT 'Montant attendu pour valider linscription',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Montant effectivement paye',
  `currency` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD' COMMENT 'Devise de paiement (USD, CDF, etc.)',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Canal: mobile_money ou card',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numero mobile pour paiement Mobile Money',
  `provider_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'orderNumber retourne par FlexPay',
  `provider_status_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code brut retourne par FlexPay',
  `provider_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Message brut retourne par FlexPay',
  `etat` enum('init','en_cours','payee','annulee','echouee','remboursee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'init' COMMENT 'Etat metier du paiement inspire de la logique FlexPay',
  `access_granted` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Vrai si paiement valide et acces evenement autorise',
  `access_granted_at` datetime DEFAULT NULL COMMENT 'Date/heure de levee d acces',
  `access_granted_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL COMMENT 'Date/heure de confirmation du paiement',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Paiement actif (non annule logiquement) ou archive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_payments_participant_event_unique` (`participant_id`,`event_id`),
  UNIQUE KEY `retreat_payments_reference_unique` (`reference`),
  KEY `retreat_payments_access_granted_by_foreign` (`access_granted_by`),
  KEY `retreat_payments_event_etat_idx` (`event_id`,`etat`),
  KEY `retreat_payments_provider_reference_index` (`provider_reference`),
  KEY `retreat_payments_access_granted_index` (`access_granted`),
  CONSTRAINT `retreat_payments_access_granted_by_foreign` FOREIGN KEY (`access_granted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `retreat_payments_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`),
  CONSTRAINT `retreat_payments_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `retreat_participant` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_participant_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant du mouvement',
  `participant_id` bigint unsigned NOT NULL,
  `event_id` bigint unsigned NOT NULL,
  `movement_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type: exit ou return',
  `moved_at` datetime NOT NULL COMMENT 'Date/heure du mouvement',
  `authorized_by` bigint unsigned DEFAULT NULL,
  `reason` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Motif de sortie/retour',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT 'Observation complementaire',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Mouvement valide ou annule',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_participant_movements_participant_id_index` (`participant_id`),
  KEY `retreat_participant_movements_event_id_index` (`event_id`),
  KEY `retreat_participant_movements_authorized_by_index` (`authorized_by`),
  KEY `retreat_participant_movements_type_date_idx` (`movement_type`,`moved_at`),
  CONSTRAINT `retreat_participant_movements_authorized_by_foreign` FOREIGN KEY (`authorized_by`) REFERENCES `users` (`id`),
  CONSTRAINT `retreat_participant_movements_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events_event` (`id`),
  CONSTRAINT `retreat_participant_movements_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `retreat_participant` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_policy_acknowledgements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant accusé de lecture',
  `policy_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `participant_id` bigint unsigned DEFAULT NULL,
  `has_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Lecture confirmee',
  `has_accepted` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Acceptation confirmee',
  `acknowledged_at` datetime DEFAULT NULL COMMENT 'Date de validation',
  `signature_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'checkbox' COMMENT 'Mode de validation: checkbox, otp, signature',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP lors de l acceptation',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Accuse de lecture actif ou annule',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_policy_acknowledgements_policy_id_index` (`policy_id`),
  KEY `retreat_policy_acknowledgements_user_id_index` (`user_id`),
  KEY `retreat_policy_acknowledgements_participant_id_index` (`participant_id`),
  CONSTRAINT `retreat_policy_acknowledgements_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `retreat_participant` (`id`),
  CONSTRAINT `retreat_policy_acknowledgements_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `retreat_policies` (`id`),
  CONSTRAINT `retreat_policy_acknowledgements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_activity_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant de pointage',
  `activity_plan_id` bigint unsigned NOT NULL,
  `participant_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent' COMMENT 'Etat: present, late, absent, excused',
  `check_in_at` datetime DEFAULT NULL COMMENT 'Heure d entree/presence',
  `check_out_at` datetime DEFAULT NULL COMMENT 'Heure de sortie',
  `scan_source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT 'Origine: manual, qr, nfc',
  `recorded_by` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci COMMENT 'Justification ou remarque',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Pointage actif ou corrige/annule',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_activity_attendance_unique` (`activity_plan_id`,`participant_id`),
  KEY `retreat_activity_attendances_participant_id_index` (`participant_id`),
  KEY `retreat_activity_attendances_recorded_by_index` (`recorded_by`),
  KEY `retreat_activity_attendance_status_scan_idx` (`status`,`scan_source`),
  CONSTRAINT `retreat_activity_attendances_activity_plan_id_foreign` FOREIGN KEY (`activity_plan_id`) REFERENCES `retreat_activity_plans` (`id`),
  CONSTRAINT `retreat_activity_attendances_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `retreat_participant` (`id`),
  CONSTRAINT `retreat_activity_attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `retreat_activity_atelier_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `activity_plan_id` bigint unsigned NOT NULL,
  `atelier_id` bigint unsigned NOT NULL,
  `sujet` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sujet de l''activité pour cet atelier',
  `texte_biblique` text COLLATE utf8mb4_unicode_ci COMMENT 'Texte biblique utilisé',
  `conducteurs` json DEFAULT NULL COMMENT 'Conducteurs du débat (ouvrier ou participant)',
  `resume` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Résumé de l''activité de l''atelier',
  `recorded_by` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retreat_activity_atelier_report_unique` (`activity_plan_id`,`atelier_id`),
  KEY `retreat_activity_atelier_reports_atelier_id_foreign` (`atelier_id`),
  KEY `retreat_activity_atelier_reports_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `retreat_activity_atelier_reports_activity_plan_id_foreign` FOREIGN KEY (`activity_plan_id`) REFERENCES `retreat_activity_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retreat_activity_atelier_reports_atelier_id_foreign` FOREIGN KEY (`atelier_id`) REFERENCES `retreat_atelier` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retreat_activity_atelier_reports_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NIVEAU 7 : transactions, SMS, sessions, watches
-- =============================================================================

CREATE TABLE IF NOT EXISTS `retreat_payment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de la transaction',
  `payment_id` bigint unsigned NOT NULL,
  `transaction_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type: initiation, callback, polling, verification',
  `provider_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'orderNumber FlexPay',
  `provider_status_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code de statut fournisseur',
  `provider_status_label` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Libelle du statut fournisseur',
  `request_payload` json DEFAULT NULL COMMENT 'Payload envoye a FlexPay',
  `response_payload` json DEFAULT NULL COMMENT 'Payload recu de FlexPay',
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Message de contexte',
  `processed_at` datetime NOT NULL COMMENT 'Date/heure de traitement transactionnel',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Ligne transaction conservee ou neutralisee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retreat_payment_transactions_payment_id_index` (`payment_id`),
  KEY `retreat_payment_transactions_provider_reference_index` (`provider_reference`),
  KEY `retreat_payment_tx_type_date_idx` (`transaction_type`,`processed_at`),
  CONSTRAINT `retreat_payment_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `retreat_payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=505 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_message_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sms_operator_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'keccel',
  `context` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `delivery_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `http_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `http_status` smallint unsigned DEFAULT NULL,
  `provider_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_response` text COLLATE utf8mb4_unicode_ci,
  `delivery_response` text COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivery_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_message_logs_provider_status_index` (`provider`,`status`),
  KEY `sms_message_logs_recipient_created_at_index` (`recipient`,`created_at`),
  KEY `sms_message_logs_sms_operator_id_foreign` (`sms_operator_id`),
  CONSTRAINT `sms_message_logs_sms_operator_id_foreign` FOREIGN KEY (`sms_operator_id`) REFERENCES `sms_operators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `watches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `watchable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `watchable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `conditions` json DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `watches_unique_subscription` (`watchable_type`,`watchable_id`,`user_id`),
  KEY `watches_watchable_type_watchable_id_index` (`watchable_type`,`watchable_id`),
  KEY `watches_user_id_paused_at_index` (`user_id`,`paused_at`),
  CONSTRAINT `watches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `watch_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `watch_id` bigint unsigned NOT NULL,
  `actor_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `diff` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `watch_events_actor_type_actor_id_index` (`actor_type`,`actor_id`),
  KEY `watch_events_watch_id_created_at_index` (`watch_id`,`created_at`),
  CONSTRAINT `watch_events_watch_id_foreign` FOREIGN KEY (`watch_id`) REFERENCES `watches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
