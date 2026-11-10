CREATE TABLE messages (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wedding_id       BIGINT UNSIGNED NOT NULL,
  created_by       BIGINT UNSIGNED NULL,
  kind             ENUM('notice','contribution_reminder','invitation') NOT NULL,
  channel          ENUM('whatsapp','sms') NOT NULL,
  body             TEXT NULL,
  recipients_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_wedding (wedding_id, created_at),
  CONSTRAINT fk_messages_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT,
  CONSTRAINT fk_messages_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_logs (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id          BIGINT UNSIGNED NOT NULL,
  wedding_id          BIGINT UNSIGNED NOT NULL,
  guest_id            BIGINT UNSIGNED NULL,
  contribution_id     BIGINT UNSIGNED NULL,
  recipient           VARCHAR(20) NOT NULL,
  status              ENUM('queued','sent','delivered','read','failed') NOT NULL DEFAULT 'queued',
  provider            VARCHAR(30) NOT NULL,
  provider_message_id VARCHAR(120) NULL,
  error               VARCHAR(255) NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_logs_message (message_id),
  KEY idx_logs_recipient (wedding_id, recipient, created_at),
  KEY idx_logs_provider (provider_message_id),
  CONSTRAINT fk_logs_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  CONSTRAINT fk_logs_wedding FOREIGN KEY (wedding_id) REFERENCES weddings(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;