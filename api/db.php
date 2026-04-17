<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!function_exists('db')) {
    function db(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $config = require __DIR__ . '/../config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        $pdo = new PDO(
            $dsn,
            $db['user'],
            $db['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;
    }
}

if (!function_exists('db_one')) {
    function db_one(string $sql, array $params = []): ?array
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}

if (!function_exists('db_all')) {
    function db_all(string $sql, array $params = []): array
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}

if (!function_exists('db_exec')) {
    function db_exec(string $sql, array $params = []): int
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->rowCount();
    }
}