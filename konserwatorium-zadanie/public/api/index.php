<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controller\CsvDataController;
use App\Repository\CsvEventRepository;
use App\Service\EventService;
use App\Service\UtmRankingService;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Access-Control-Allow-Origin: *');

$mapping = require __DIR__ . '/../../config/csv_mapping.php';
$csvPath = realpath(__DIR__ . '/../../data/tickets.csv');

$repository = new CsvEventRepository($csvPath, $mapping);
$controller = new CsvDataController(
    eventService:      new EventService($repository),
    utmRankingService: new UtmRankingService($repository),
);

try {
    $controller->handle();
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
}
