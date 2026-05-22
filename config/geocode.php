<?php

declare(strict_types=1);

function normalizeSearchText(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    return $text;
}

function similarityScore(string $input, string $candidate): float
{
    $input = normalizeSearchText($input);
    $candidate = normalizeSearchText($candidate);

    if ($input === '' || $candidate === '') {
        return 0.0;
    }

    if ($input === $candidate) {
        return 100.0;
    }

    if (str_contains($candidate, $input) || str_contains($input, $candidate)) {
        return 92.0;
    }

    similar_text($input, $candidate, $percent);
    $lev = levenshtein(
        substr($input, 0, 255),
        substr($candidate, 0, 255)
    );
    $maxLen = max(strlen($input), strlen($candidate), 1);
    $levPercent = (1 - ($lev / $maxLen)) * 100;

    return round(max($percent, $levPercent), 1);
}

function formatPlaceLabel(array $place): string
{
    $parts = array_filter([
        $place['name'] ?? '',
        $place['state'] ?? '',
        $place['country'] ?? '',
    ]);

    return implode(', ', $parts);
}

function fetchGeocodeResults(string $query, string $apiKey, int $limit = 10): array
{
    $url = 'https://api.openweathermap.org/geo/1.0/direct?' . http_build_query([
        'q' => $query,
        'limit' => $limit,
        'appid' => $apiKey,
    ]);

    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);

    return is_array($data) ? $data : [];
}

/**
 * Find cities similar to the user query (handles typos and partial names).
 *
 * @return list<array{name: string, state: string, country: string, label: string, lat: float, lon: float, score: float}>
 */
function findSimilarCities(string $userQuery, string $apiKey, int $maxResults = 5): array
{
    $query = trim($userQuery);

    if ($query === '') {
        return [];
    }

    $variants = [$query];
    $length = strlen($query);

    if ($length >= 5) {
        $variants[] = substr($query, 0, $length - 1);
        $variants[] = substr($query, 0, $length - 2);
    }

    if ($length >= 3) {
        $variants[] = substr($query, 0, (int) ceil($length * 0.65));
    }

    $candidates = [];

    foreach (array_unique($variants) as $variant) {
        if (strlen($variant) < 2) {
            continue;
        }

        foreach (fetchGeocodeResults($variant, $apiKey, 12) as $place) {
            $lat = (float) ($place['lat'] ?? 0);
            $lon = (float) ($place['lon'] ?? 0);
            $key = round($lat, 2) . ',' . round($lon, 2);
            $candidates[$key] = $place;
        }
    }

    $scored = [];

    foreach ($candidates as $place) {
        $name = (string) ($place['name'] ?? '');
        $state = (string) ($place['state'] ?? '');
        $country = (string) ($place['country'] ?? '');
        $label = formatPlaceLabel($place);

        $score = max(
            similarityScore($query, $name),
            similarityScore($query, $label),
            similarityScore($query, $name . ' ' . $country)
        );

        if ($score < 40) {
            continue;
        }

        $scored[] = [
            'name' => $name,
            'state' => $state,
            'country' => $country,
            'label' => $label,
            'lat' => (float) $place['lat'],
            'lon' => (float) $place['lon'],
            'score' => $score,
        ];
    }

    usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

    $unique = [];
    $seenLabels = [];

    foreach ($scored as $item) {
        $labelKey = normalizeSearchText($item['label']);

        if (isset($seenLabels[$labelKey])) {
            continue;
        }

        $seenLabels[$labelKey] = true;
        $unique[] = $item;

        if (count($unique) >= $maxResults) {
            break;
        }
    }

    return $unique;
}

function formatSuggestion(array $match): array
{
    return [
        'name' => $match['name'],
        'state' => $match['state'],
        'country' => $match['country'],
        'label' => $match['label'],
        'lat' => $match['lat'],
        'lon' => $match['lon'],
        'score' => $match['score'],
    ];
}
