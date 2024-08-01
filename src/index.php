<?php

require 'vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;


$host = getenv('DATABASE_HOST');
$db = getenv('DATABASE_NAME');
$user = getenv('DATABASE_USER');
$pass = getenv('DATABASE_PASSWORD');
$port = getenv('DATABASE_PORT');

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}


$adapter = new APC();
$registry = new CollectorRegistry($adapter);

$rpsCounter = $registry->getOrRegisterCounter('app', 'http_requests_total', 'Total number of HTTP requests', ['method', 'endpoint']);
$errorCounter = $registry->getOrRegisterCounter('app', 'http_errors_total', 'Total number of HTTP errors', ['method', 'endpoint', 'status']);

$latencyHistogram = $registry->getOrRegisterHistogram('app', 'http_request_duration_seconds', 'HTTP request latency', ['method', 'endpoint'], [0.1, 0.5, 1, 2, 5]);

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$request = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
$resource = array_shift($request);

if ($resource === 'metrics') {
    $renderer = new RenderTextFormat();
    $result = $renderer->render($registry->getMetricFamilySamples());

    header('Content-type: ' . RenderTextFormat::MIME_TYPE);
    echo $result;
    exit;
}

$start = microtime(true);

if ($resource !== 'users') {
    http_response_code(404);
    $errorCounter->inc([$method, $requestUri, '404']);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

switch ($method) {
    case 'GET':
        if (isset($request[0]) && is_numeric($request[0])) {
            $id = (int) $request[0];
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->query("SELECT * FROM users");
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode($response);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['email']]);
        echo json_encode(['id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $id = (int) $request[0];
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['email'], $id]);
        echo json_encode(['status' => 'success']);
        break;

    case 'DELETE':
        $id = (int) $request[0];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
        break;

    default:
        http_response_code(405);
        $errorCounter->inc([$method, $requestUri, '405']);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

$rpsCounter->inc([$method, $requestUri]);

$duration = microtime(true) - $start;
$latencyHistogram->observe($duration, [$method, $requestUri]);
