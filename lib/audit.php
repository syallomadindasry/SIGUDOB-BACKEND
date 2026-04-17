<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/db.php';

if (!function_exists('audit_log')) {
    function audit_log(?int $actorId, string $action, string $entity, ?int $entityId = null, array $payload = []): void
    {
        static $auditTableChecked = false;
        static $auditTableExists = false;

        if (!$auditTableChecked) {
            $auditTableChecked = true;

            try {
                $stmt = db()->prepare(
                    "SELECT 1
                     FROM INFORMATION_SCHEMA.TABLES
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'audit_logs'
                     LIMIT 1"
                );
                $stmt->execute();
                $auditTableExists = (bool) $stmt->fetchColumn();
            } catch (Throwable $e) {
                error_log('audit_log table check failed: ' . $e->getMessage());
                $auditTableExists = false;
            }
        }

        if (!$auditTableExists) {
            return;
        }

        try {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null;

            $payloadJson = null;
            if (!empty($payload)) {
                $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $payloadJson = $encoded === false ? '{}' : $encoded;
            }

            db_exec(
                "INSERT INTO audit_logs (actor_id, action, entity, entity_id, payload, ip) VALUES (?,?,?,?,?,?)",
                [
                    $actorId,
                    $action,
                    $entity,
                    $entityId,
                    $payloadJson,
                    $ip,
                ]
            );
        } catch (Throwable $e) {
            error_log('audit_log insert failed in ' . __FILE__ . ': ' . $e->getMessage());
        }
    }
}