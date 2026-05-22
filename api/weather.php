<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/geocode.php';

$apiKey = getApiKey();
$city = trim($_GET['city'] ?? '');
$lat = $_GET['lat'] ?? '';
$lon = $_GET['lon'] ?? '';

if ($city === '' && ($lat === '' || $lon === '')) {
    jsonResponse([
        'success' => false,
        'message' => 'Please provide a city name or coordinates.',
    ], 400);
}

$matchedFrom = null;

if ($city !== '') {
    $similar = findSimilarCities($city, $apiKey, 5);

    if (!empty($similar) && $similar[0]['score'] >= 72) {
        $best = $similar[0];
        $lat = (string) $best['lat'];
        $lon = (string) $best['lon'];
        $matchedFrom = [
            'query' => $city,
            'label' => $best['label'],
            'score' => $best['score'],
        ];
        $city = '';
    } else {
        $query = http_build_query([
            'q' => $city,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);
        $url = "https://api.openweathermap.org/data/2.5/weather?{$query}";
        $data = fetchOpenWeather($url);

        if (isset($data['cod']) && (int) $data['cod'] === 200) {
            $payload = formatWeatherPayload($data);
            jsonResponse([
                'success' => true,
                'data' => $payload,
            ]);
        }

        if (!empty($similar)) {
            jsonResponse([
                'success' => false,
                'message' => 'City not found. Did you mean one of these?',
                'suggestions' => array_map('formatSuggestion', $similar),
            ], 404);
        }

        $message = match ((string) ($data['cod'] ?? '')) {
            '404' => 'City not found. Try a different spelling or pick a suggestion.',
            '401' => 'Invalid API key. Verify OPENWEATHER_API_KEY in .env.',
            default => $data['message'] ?? 'Weather data unavailable.',
        };

        jsonResponse([
            'success' => false,
            'message' => $message,
        ], 400);
    }
}

if (!is_numeric($lat) || !is_numeric($lon)) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid coordinates.',
    ], 400);
}

$query = http_build_query([
    'lat' => $lat,
    'lon' => $lon,
    'appid' => $apiKey,
    'units' => 'metric',
]);

$url = "https://api.openweathermap.org/data/2.5/weather?{$query}";
$data = fetchOpenWeather($url);

if (isset($data['cod']) && (int) $data['cod'] !== 200) {
    jsonResponse([
        'success' => false,
        'message' => $data['message'] ?? 'Weather data unavailable.',
    ], 400);
}

$payload = formatWeatherPayload($data);

if ($matchedFrom !== null) {
    $payload['correctedFrom'] = $matchedFrom['query'];
    $payload['matchedLabel'] = $matchedFrom['label'];
}

jsonResponse([
    'success' => true,
    'data' => $payload,
]);
