<?php
// FILE: backend/api/db.php

if (!function_exists('db')) {
  function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
      $config = require __DIR__ . '/../config.php';
      $db = $config['db'];

      $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

      $pdo = new PDO(
        $dsn,
        $db['user'],
        $db['pass'],
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
      );
    }

    return $pdo;
  }
}

if (!function_exists('db_one')) {
  function db_one(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
  }
}

if (!function_exists('db_all')) {
  function db_all(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
}

if (!function_exists('db_exec')) {
  function db_exec(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->rowCount();
  }
}