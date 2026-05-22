<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/geocode.php';

$apiKey = getApiKey();
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    jsonResponse([
        'success' => true,
        'data' => [],
    ]);
}

$matches = findSimilarCities($query, $apiKey, 6);

jsonResponse([
    'success' => true,
    'data' => array_map('formatSuggestion', $matches),
]);
