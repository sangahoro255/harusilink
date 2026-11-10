CREATE TABLE guest_categories (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id BIGINT UNSIGNED NOT NULL,
  code       VARCHAR(30) NOT NULL,
  name       VARCHAR(60) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cat_wedding_code (wedding_id, code),
  CONSTRAINT fk_cat_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guests (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id  BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  full_name   VARCHAR(120) NOT NULL,
  phone       VARCHAR(20) NULL,
  email       VARCHAR(190) NULL,
  seats       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  rsvp_status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  KEY idx_guests_wedding (wedding_id, deleted_at),
  KEY idx_guests_wedding_rsvp (wedding_id, rsvp_status),
  KEY idx_guests_wedding_name (wedding_id, full_name),
  CONSTRAINT fk_guests_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT,
  CONSTRAINT fk_guests_category FOREIGN KEY (category_id) REFERENCES guest_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;