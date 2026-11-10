CREATE TABLE wedding_settings (
  wedding_id         BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  template           VARCHAR(30) NOT NULL DEFAULT 'classic',
  palette            VARCHAR(30) NOT NULL DEFAULT 'navy_gold',
  primary_color      CHAR(7) NOT NULL DEFAULT '#0B1F3A',
  accent_color       CHAR(7) NOT NULL DEFAULT '#D4A72C',
  invitation_message TEXT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_settings_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;