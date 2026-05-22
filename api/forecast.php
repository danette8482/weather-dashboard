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

if ($city !== '') {
    $similar = findSimilarCities($city, $apiKey, 1);

    if (!empty($similar) && $similar[0]['score'] >= 72) {
        $lat = (string) $similar[0]['lat'];
        $lon = (string) $similar[0]['lon'];
        $city = '';
    } else {
        $query = http_build_query([
            'q' => $city,
            'appid' => $apiKey,
            'units' => 'metric',
            'cnt' => 40,
        ]);
    }
}

if ($city === '') {
    $query = http_build_query([
        'lat' => $lat,
        'lon' => $lon,
        'appid' => $apiKey,
        'units' => 'metric',
        'cnt' => 40,
    ]);
}

$url = "https://api.openweathermap.org/data/2.5/forecast?{$query}";
$data = fetchOpenWeather($url);

if (isset($data['cod']) && (string) $data['cod'] !== '200') {
    jsonResponse([
        'success' => false,
        'message' => $data['message'] ?? 'Forecast unavailable.',
    ], 400);
}

$timezone = (int) ($data['city']['timezone'] ?? 0);
$now = time();
$hours = [];
$hourCount = 0;

foreach ($data['list'] ?? [] as $index => $item) {
    if ($hourCount >= 8) {
        break;
    }

    $ts = (int) ($item['dt'] ?? 0);

    if ($ts < $now - 3600) {
        continue;
    }

    $weather = $item['weather'][0] ?? [];
    $icon = $weather['icon'] ?? '01d';
    $localTs = $ts + $timezone;

    $hours[] = [
        'time' => $hourCount === 0 ? 'NOW' : gmdate('g A', $localTs),
        'temp' => round((float) ($item['main']['temp'] ?? 0)),
        'iconUrl' => "https://openweathermap.org/img/wn/{$icon}@2x.png",
        'isNow' => $hourCount === 0,
    ];

    $hourCount++;
}

$dailyBuckets = [];

foreach ($data['list'] ?? [] as $item) {
    $date = substr($item['dt_txt'] ?? '', 0, 10);

    if ($date === '') {
        continue;
    }

    if (!isset($dailyBuckets[$date])) {
        $dailyBuckets[$date] = [
            'temps' => [],
            'icons' => [],
            'conditions' => [],
        ];
    }

    $dailyBuckets[$date]['temps'][] = (float) ($item['main']['temp'] ?? 0);
    $dailyBuckets[$date]['temps'][] = (float) ($item['main']['temp_min'] ?? 0);
    $dailyBuckets[$date]['temps'][] = (float) ($item['main']['temp_max'] ?? 0);

    $weather = $item['weather'][0] ?? [];
    $dailyBuckets[$date]['icons'][] = $weather['icon'] ?? '01d';
    $dailyBuckets[$date]['conditions'][] = $weather['main'] ?? 'N/A';
}

$today = gmdate('Y-m-d', $now + $timezone);
$days = [];
$dayIndex = 0;

foreach ($dailyBuckets as $date => $bucket) {
    if ($dayIndex >= 7) {
        break;
    }

    $icon = $bucket['icons'][(int) floor(count($bucket['icons']) / 2)] ?? '01d';
    $tempMin = (int) round(min($bucket['temps']));
    $tempMax = (int) round(max($bucket['temps']));

    if ($date === $today) {
        $label = 'Today';
    } else {
        $label = gmdate('D', strtotime($date . ' 12:00:00') + $timezone);
    }

    $days[] = [
        'label' => $label,
        'date' => $date,
        'tempMin' => $tempMin,
        'tempMax' => $tempMax,
        'condition' => $bucket['conditions'][0] ?? 'N/A',
        'iconUrl' => "https://openweathermap.org/img/wn/{$icon}@2x.png",
    ];

    $dayIndex++;
}

$allMins = array_column($days, 'tempMin');
$allMaxs = array_column($days, 'tempMax');
$rangeMin = !empty($allMins) ? min($allMins) : 0;
$rangeMax = !empty($allMaxs) ? max($allMaxs) : 30;

jsonResponse([
    'success' => true,
    'data' => [
        'city' => $data['city']['name'] ?? '',
        'hours' => $hours,
        'days' => $days,
        'rangeMin' => $rangeMin,
        'rangeMax' => $rangeMax,
    ],
]);
