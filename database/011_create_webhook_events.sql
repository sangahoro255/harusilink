CREATE TABLE webhook_events (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider   VARCHAR(30) NOT NULL,
  event_id   VARCHAR(190) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_webhook_event (provider, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE invitations
  ADD COLUMN sent_at DATETIME NULL,
  ADD COLUMN sent_channel VARCHAR(20) NULL;