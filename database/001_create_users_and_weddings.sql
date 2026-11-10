CREATE TABLE users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  phone         VARCHAR(30)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('super_admin','owner') NOT NULL DEFAULT 'owner',
  status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME NULL,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE weddings (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id         BIGINT UNSIGNED NOT NULL,
  slug             VARCHAR(160) NOT NULL,
  bride_name       VARCHAR(100) NOT NULL,
  groom_name       VARCHAR(100) NOT NULL,
  wedding_date     DATE NOT NULL,
  wedding_time     TIME NULL,
  venue            VARCHAR(255) NOT NULL,
  description      TEXT NULL,
  cover_image      VARCHAR(255) NULL,
  scanner_pin_hash VARCHAR(255) NULL,
  status           ENUM('draft','active','completed','archived') NOT NULL DEFAULT 'draft',
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  UNIQUE KEY uq_weddings_slug (slug),
  KEY idx_weddings_owner (owner_id),
  KEY idx_weddings_status (status),
  CONSTRAINT fk_weddings_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;