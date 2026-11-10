CREATE TABLE rsvp_responses (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id BIGINT UNSIGNED NOT NULL,
  guest_id   BIGINT UNSIGNED NOT NULL,
  status     ENUM('pending','accepted','declined') NOT NULL,
  source     VARCHAR(20) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rsvp_wedding (wedding_id, created_at),
  KEY idx_rsvp_guest (guest_id, created_at),
  CONSTRAINT fk_rsvp_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT,
  CONSTRAINT fk_rsvp_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;