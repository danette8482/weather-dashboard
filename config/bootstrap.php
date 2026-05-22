<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getApiKey(): string
{
    $key = env('OPENWEATHER_API_KEY');

    if ($key === null || $key === '' || $key === 'your_api_key_here') {
        jsonResponse([
            'success' => false,
            'message' => 'API key not configured. Add OPENWEATHER_API_KEY to your .env file.',
        ], 500);
    }

    return $key;
}

function fetchOpenWeather(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        jsonResponse([
            'success' => false,
            'message' => 'Unable to reach weather service. Check your connection.',
        ], 502);
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        jsonResponse([
            'success' => false,
            'message' => 'Invalid response from weather service.',
        ], 502);
    }

    return $data;
}

function msToKmh(float $speedMs): float
{
    return round($speedMs * 3.6, 1);
}

function formatLocalTime(int $timestamp, int $timezoneOffset): string
{
    return gmdate('h:i A', $timestamp + $timezoneOffset);
}

function formatWeatherPayload(array $data): array
{
    $weather = $data['weather'][0] ?? [];
    $icon = $weather['icon'] ?? '01d';
    $timezone = (int) ($data['timezone'] ?? 0);
    $countryCode = $data['sys']['country'] ?? '';
    $countryName = $countryCode;

    $sunrise = isset($data['sys']['sunrise'])
        ? formatLocalTime((int) $data['sys']['sunrise'], $timezone)
        : null;
    $sunset = isset($data['sys']['sunset'])
        ? formatLocalTime((int) $data['sys']['sunset'], $timezone)
        : null;

    $localTs = time() + $timezone;

    return [
        'city' => $data['name'] ?? 'Unknown',
        'country' => $countryCode,
        'countryName' => $countryName,
        'locationLabel' => strtoupper(($data['name'] ?? 'Unknown') . ' · ' . $countryCode),
        'temperature' => isset($data['main']['temp']) ? round((float) $data['main']['temp']) : null,
        'feelsLike' => isset($data['main']['feels_like']) ? round((float) $data['main']['feels_like']) : null,
        'humidity' => $data['main']['humidity'] ?? null,
        'pressure' => $data['main']['pressure'] ?? null,
        'windSpeed' => isset($data['wind']['speed']) ? msToKmh((float) $data['wind']['speed']) : null,
        'condition' => $weather['main'] ?? 'N/A',
        'description' => ucfirst($weather['description'] ?? ''),
        'icon' => $icon,
        'iconUrl' => "https://openweathermap.org/img/wn/{$icon}@4x.png",
        'sunrise' => $sunrise,
        'sunset' => $sunset,
        'localDateTime' => gmdate('l h:i A', $localTs),
        'timezone' => $timezone,
    ];
}
