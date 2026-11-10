CREATE TABLE invitations (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id BIGINT UNSIGNED NOT NULL,
  guest_id   BIGINT UNSIGNED NOT NULL,
  token      CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  revoked_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invitations_token (token),
  UNIQUE KEY uq_invitations_guest (guest_id),
  KEY idx_invitations_wedding (wedding_id),
  CONSTRAINT fk_invitations_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT,
  CONSTRAINT fk_invitations_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;