ALTER TABLE wedding_settings
  ADD COLUMN contribution_target DECIMAL(14,2) NULL,
  ADD COLUMN contribution_deadline DATE NULL,
  ADD COLUMN contribution_description TEXT NULL,
  ADD COLUMN payment_info TEXT NULL;

CREATE TABLE contributions (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id       BIGINT UNSIGNED NOT NULL,
  contributor_name VARCHAR(120) NOT NULL,
  phone            VARCHAR(20) NULL,
  amount           DECIMAL(14,2) NOT NULL,
  method           VARCHAR(20) NOT NULL DEFAULT 'cash',
  reference        VARCHAR(80) NULL,
  status           ENUM('pledged','paid','cancelled') NOT NULL DEFAULT 'paid',
  contributed_on   DATE NOT NULL,
  note             VARCHAR(255) NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  KEY idx_contrib_wedding (wedding_id, deleted_at, status),
  KEY idx_contrib_phone (wedding_id, phone),
  CONSTRAINT fk_contrib_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;