<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app-bg" aria-hidden="true"></div>

<div class="app-shell">
    <header class="top-bar">
        <a class="brand" href="#">
            <span class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
            </span>
            <span class="brand-text">
                <span class="brand-eyebrow">LIVE</span>
                <span class="brand-name">Weather Dashboard</span>
            </span>
        </a>

        <div class="search-area">
            <div class="search-wrap">
                <input
                    type="text"
                    id="cityInput"
                    class="search-input"
                    placeholder="Search any city…"
                    autocomplete="off"
                    aria-label="Search city"
                    aria-autocomplete="list"
                    aria-controls="citySuggestions"
                    aria-expanded="false"
                >
                <button type="button" class="search-submit" id="searchBtn" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                </button>
                <ul id="citySuggestions" class="city-suggestions hidden" role="listbox"></ul>
            </div>
            <button type="button" class="btn-geo" id="locationBtn" title="Use current location">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2a8 8 0 0 1 8 8c0 5.25-8 12-8 12S4 15.25 4 10a8 8 0 0 1 8-8z"/><circle cx="12" cy="10" r="2.5"/></svg>
            </button>
        </div>
    </header>

    <div id="loading" class="state-panel hidden" role="status" aria-live="polite">
        <div class="loader"></div>
        <p>Fetching weather data…</p>
    </div>

    <div id="error" class="alert-error hidden" role="alert"></div>
    <div id="similarCities" class="similar-cities hidden" role="region" aria-label="Similar cities"></div>

    <main id="dashboard" class="dashboard hidden">
        <!-- Hero: layout differs on mobile vs laptop via CSS -->
        <section class="glass hero-panel" aria-label="Current weather">
            <div class="hero-content">
                <p class="location-line">
                    <svg class="pin-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                    <span id="locationLabel">—</span>
                </p>
                <p id="correctedNote" class="corrected-note hidden"></p>
                <div class="hero-temp-row">
                    <div class="hero-temp-block">
                        <h1 class="hero-temp"><span id="temperature">—</span><span class="deg">°C</span></h1>
                        <p class="hero-condition"><span id="condition">—</span> · Feels <span id="feelsLike">—</span>°</p>
                        <p id="dateTime" class="hero-datetime">—</p>
                    </div>
                    <div class="hero-visual">
                        <img id="weatherIcon" src="" alt="" class="hero-icon">
                    </div>
                </div>
            </div>
        </section>

        <section class="metrics-row" aria-label="Weather details">
            <article class="glass metric-card">
                <span class="metric-icon">💧</span>
                <span class="metric-label">Humidity</span>
                <span class="metric-value"><span id="humidity">—</span>%</span>
            </article>
            <article class="glass metric-card">
                <span class="metric-icon">🌬</span>
                <span class="metric-label">Wind</span>
                <span class="metric-value"><span id="wind">—</span> km/h</span>
            </article>
            <article class="glass metric-card">
                <span class="metric-icon">🌡</span>
                <span class="metric-label">Feels</span>
                <span class="metric-value"><span id="feelsLikeMetric">—</span>°</span>
            </article>
            <article class="glass metric-card">
                <span class="metric-icon">◎</span>
                <span class="metric-label">Pressure</span>
                <span class="metric-value"><span id="pressure">—</span></span>
            </article>
            <article class="glass metric-card">
                <span class="metric-icon">↑</span>
                <span class="metric-label">Sunrise</span>
                <span class="metric-value"><span id="sunrise">—</span></span>
            </article>
            <article class="glass metric-card">
                <span class="metric-icon">↓</span>
                <span class="metric-label">Sunset</span>
                <span class="metric-value"><span id="sunset">—</span></span>
            </article>
        </section>

        <section class="panels-row">
            <article class="glass panel hourly-panel">
                <div class="panel-head">
                    <h2>Next Hours</h2>
                </div>
                <div id="hourlyList" class="hourly-scroll"></div>
            </article>

            <article class="glass panel week-panel">
                <div class="panel-head">
                    <h2>Week Ahead</h2>
                </div>
                <div id="weekList" class="week-list"></div>
            </article>
        </section>
    </main>

    <footer class="app-footer">
        <span>WEATHER DASHBOARD · <?php echo date('Y'); ?></span>
        <span>Powered by OpenWeather</span>
    </footer>
</div>

<script src="script.js"></script>
</body>
</html>
