<?php
// FILE: backend/lib/audit.php

require_once __DIR__ . '/../api/db.php';

if (!function_exists('audit_log')) {
  function audit_log(?int $actorId, string $action, string $entity, ?int $entityId = null, array $payload = []): void {
    static $auditTableChecked = false;
    static $auditTableExists = false;

    if (!$auditTableChecked) {
      $auditTableChecked = true;
      try {
        $stmt = db()->prepare(
          "SELECT 1
           FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs'
           LIMIT 1"
        );
        $stmt->execute();
        $auditTableExists = (bool)$stmt->fetchColumn();
      } catch (Throwable $e) {
        $auditTableExists = false;
      }
    }

    if (!$auditTableExists) {
      return;
    }

    try {
      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
      $payloadJson = $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;

      db_exec(
        "INSERT INTO audit_logs (actor_id, action, entity, entity_id, payload, ip) VALUES (?,?,?,?,?,?)",
        [$actorId, $action, $entity, $entityId, $payloadJson, $ip]
      );
    } catch (Throwable $e) {
      // Audit log tidak boleh menjatuhkan request utama.
    }
  }
}
