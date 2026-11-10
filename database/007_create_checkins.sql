CREATE TABLE guest_checkins (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id    BIGINT UNSIGNED NOT NULL,
  guest_id      BIGINT UNSIGNED NOT NULL,
  seats         TINYINT UNSIGNED NOT NULL,
  rsvp_at_entry ENUM('pending','accepted','declined') NOT NULL,
  method        VARCHAR(20) NOT NULL DEFAULT 'qr',
  checked_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_checkins_guest (guest_id),
  KEY idx_checkins_wedding (wedding_id, checked_in_at),
  CONSTRAINT fk_checkins_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT,
  CONSTRAINT fk_checkins_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scanner_attempts (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip         VARCHAR(45) NOT NULL,
  wedding_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_ip (ip, created_at),
  KEY idx_attempts_wedding (wedding_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;