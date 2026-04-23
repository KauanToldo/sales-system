<?php

declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$dbHost = getenv('APP_DB_HOST') ?: 'db';
$dbPort = getenv('APP_DB_PORT') ?: '5432';
$dbName = getenv('APP_DB_NAME') ?: 'app';
$dbUser = getenv('APP_DB_USER') ?: 'user';
$dbPassword = getenv('APP_DB_PASSWORD') ?: 'password';

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $dbHost, $dbPort, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($uri === '/users') {
    try {
        $stmt = $pdo->query('SELECT id, name, email FROM users ORDER BY id');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch users']);
    }

    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Route not found']);