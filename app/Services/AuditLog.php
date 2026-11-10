<?php
namespace App\Services;

class AuditLog
{
    public static function record(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $details = []
    ): void {
        try {
            $stmt = db()->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, details)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :ip, :details)'
            );
            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
                ':details'     => $details ? json_encode($details) : null,
            ]);
        } catch (\PDOException $ex) {
            error_log($ex->getMessage());
        }
    }
}