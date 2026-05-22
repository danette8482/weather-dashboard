const API_BASE = "api";
const SUGGEST_MIN_CHARS = 2;
const SUGGEST_DEBOUNCE_MS = 300;
const DEFAULT_CITY = "India";

const searchBtn = document.getElementById("searchBtn");
const locationBtn = document.getElementById("locationBtn");
const cityInput = document.getElementById("cityInput");
const citySuggestionsEl = document.getElementById("citySuggestions");
const similarCitiesEl = document.getElementById("similarCities");

const loadingEl = document.getElementById("loading");
const errorEl = document.getElementById("error");
const dashboardEl = document.getElementById("dashboard");

let suggestTimer = null;
let lastForecastRange = { min: 0, max: 30 };

searchBtn.addEventListener("click", () => searchByCity());
locationBtn.addEventListener("click", () => searchByLocation());

cityInput.addEventListener("keypress", (event) => {
    if (event.key === "Enter") {
        hideSuggestions();
        searchByCity();
    }
});

cityInput.addEventListener("input", () => {
    hideSimilarCities();
    const query = cityInput.value.trim();
    clearTimeout(suggestTimer);

    if (query.length < SUGGEST_MIN_CHARS) {
        hideSuggestions();
        return;
    }

    suggestTimer = setTimeout(() => fetchSuggestions(query), SUGGEST_DEBOUNCE_MS);
});

cityInput.addEventListener("focus", () => {
    const query = cityInput.value.trim();
    if (query.length >= SUGGEST_MIN_CHARS) {
        fetchSuggestions(query);
    }
});

document.addEventListener("click", (event) => {
    if (!event.target.closest(".search-wrap")) {
        hideSuggestions();
    }
});

function searchByCity() {
    const city = cityInput.value.trim();
    if (city === "") {
        showError("Please enter a city name.");
        return;
    }
    hideSuggestions();
    hideSimilarCities();
    loadAll({ city });
}

function searchByLocation() {
    if (!navigator.geolocation) {
        showError("Geolocation is not supported by your browser.");
        return;
    }

    setLoading(true);
    hideError();
    hideSimilarCities();

    navigator.geolocation.getCurrentPosition(
        (position) => {
            loadAll({
                lat: position.coords.latitude,
                lon: position.coords.longitude,
            });
        },
        () => {
            setLoading(false);
            showError("Unable to access your location. Please allow location permission.");
        },
        { enableHighAccuracy: false, timeout: 10000 }
    );
}

function loadAll(params) {
    fetchWeather(params);
    fetchForecast(params);
}

function selectCity(suggestion) {
    cityInput.value = suggestion.label;
    hideSuggestions();
    hideSimilarCities();
    hideError();
    loadAll({ lat: suggestion.lat, lon: suggestion.lon });
}

async function fetchSuggestions(query) {
    try {
        const response = await fetch(
            `${API_BASE}/suggest.php?q=${encodeURIComponent(query)}`
        );
        const result = await response.json();
        if (!result.success || !result.data?.length) {
            hideSuggestions();
            return;
        }
        renderSuggestions(result.data);
    } catch {
        hideSuggestions();
    }
}

function renderSuggestions(items) {
    citySuggestionsEl.innerHTML = "";
    citySuggestionsEl.classList.remove("hidden");
    cityInput.setAttribute("aria-expanded", "true");

    items.forEach((item) => {
        const li = document.createElement("li");
        li.setAttribute("role", "option");
        const btn = document.createElement("button");
        btn.type = "button";
        btn.innerHTML = `${escapeHtml(item.label)}<span class="suggest-meta">Match ${Math.round(item.score)}%</span>`;
        btn.addEventListener("click", () => selectCity(item));
        li.appendChild(btn);
        citySuggestionsEl.appendChild(li);
    });
}

function hideSuggestions() {
    citySuggestionsEl.classList.add("hidden");
    citySuggestionsEl.innerHTML = "";
    cityInput.setAttribute("aria-expanded", "false");
}

function showSimilarCities(message, suggestions) {
    similarCitiesEl.classList.remove("hidden");
    similarCitiesEl.innerHTML = `<p>${escapeHtml(message)}</p><div class="suggestion-chips"></div>`;
    const chips = similarCitiesEl.querySelector(".suggestion-chips");
    suggestions.forEach((item) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "chip-btn";
        btn.textContent = item.label;
        btn.addEventListener("click", () => selectCity(item));
        chips.appendChild(btn);
    });
}

function hideSimilarCities() {
    similarCitiesEl.classList.add("hidden");
    similarCitiesEl.innerHTML = "";
}

async function fetchWeather(params) {
    setLoading(true);
    hideError();
    hideSimilarCities();
    dashboardEl.classList.add("hidden");

    try {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`${API_BASE}/weather.php?${query}`);
        const result = await response.json();
        setLoading(false);

        if (!result.success) {
            showError(result.message || "Weather data unavailable.");
            if (result.suggestions?.length) {
                showSimilarCities("Did you mean:", result.suggestions);
            }
            return;
        }

        renderWeather(result.data);
        showDashboard();
    } catch {
        setLoading(false);
        showError("Network error. Please try again.");
    }
}

async function fetchForecast(params) {
    try {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`${API_BASE}/forecast.php?${query}`);
        const result = await response.json();

        if (!result.success) {
            return;
        }

        lastForecastRange = {
            min: result.data.rangeMin ?? 0,
            max: result.data.rangeMax ?? 30,
        };

        if (result.data.hours?.length) {
            renderHourly(result.data.hours);
        }

        if (result.data.days?.length) {
            renderWeek(result.data.days, lastForecastRange);
        }
    } catch {
        /* supplementary */
    }
}

function renderWeather(data) {
    const locationEl = document.getElementById("locationLabel");
    locationEl.textContent =
        data.locationLabel ||
        `${(data.city || "").toUpperCase()} · ${data.country || ""}`;

    const correctedNote = document.getElementById("correctedNote");
    if (data.correctedFrom && data.matchedLabel) {
        correctedNote.textContent = `Matched "${data.matchedLabel}" from "${data.correctedFrom}"`;
        correctedNote.classList.remove("hidden");
        cityInput.value = data.matchedLabel;
    } else {
        correctedNote.classList.add("hidden");
        correctedNote.textContent = "";
    }

    document.getElementById("temperature").textContent = data.temperature ?? "—";
    document.getElementById("condition").textContent = data.condition ?? "—";
    document.getElementById("feelsLike").textContent = data.feelsLike ?? "—";
    document.getElementById("feelsLikeMetric").textContent = data.feelsLike ?? "—";
    document.getElementById("humidity").textContent = data.humidity ?? "—";
    document.getElementById("wind").textContent = data.windSpeed ?? "—";
    document.getElementById("pressure").textContent = data.pressure ?? "—";
    document.getElementById("sunrise").textContent = data.sunrise ?? "—";
    document.getElementById("sunset").textContent = data.sunset ?? "—";
    document.getElementById("dateTime").textContent = data.localDateTime ?? "—";

    const iconEl = document.getElementById("weatherIcon");
    iconEl.src = data.iconUrl ?? "";
    iconEl.alt = `${data.condition} weather`;
}

function renderHourly(hours) {
    const list = document.getElementById("hourlyList");
    list.innerHTML = "";

    hours.forEach((hour) => {
        const pill = document.createElement("div");
        pill.className = `hour-pill${hour.isNow ? " active" : ""}`;
        pill.innerHTML = `
            <p class="hour-time">${escapeHtml(hour.time)}</p>
            <img src="${escapeHtml(hour.iconUrl)}" alt="" width="36" height="36" loading="lazy">
            <p class="hour-temp">${hour.temp}°</p>
        `;
        list.appendChild(pill);
    });
}

function renderWeek(days, range) {
    const list = document.getElementById("weekList");
    list.innerHTML = "";

    const span = Math.max(range.max - range.min, 1);

    days.forEach((day) => {
        const leftPct = ((day.tempMin - range.min) / span) * 100;
        const widthPct = Math.max(((day.tempMax - day.tempMin) / span) * 100, 8);

        const row = document.createElement("div");
        row.className = "week-row";
        row.innerHTML = `
            <span class="week-day">${escapeHtml(day.label)}</span>
            <img src="${escapeHtml(day.iconUrl)}" alt="" width="28" height="28" loading="lazy">
            <div class="temp-bar-wrap">
                <div class="temp-bar-fill" style="left:${leftPct}%;width:${widthPct}%"></div>
            </div>
            <span class="week-temps">${day.tempMin}° / ${day.tempMax}°</span>
        `;
        list.appendChild(row);
    });
}

function showDashboard() {
    dashboardEl.classList.remove("hidden");
}

function setLoading(show) {
    loadingEl.classList.toggle("hidden", !show);
}

function showError(message) {
    errorEl.classList.remove("hidden");
    errorEl.textContent = message;
    dashboardEl.classList.add("hidden");
}

function hideError() {
    errorEl.classList.add("hidden");
    errorEl.textContent = "";
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text ?? "";
    return div.innerHTML;
}

document.addEventListener("DOMContentLoaded", () => {
    cityInput.value = DEFAULT_CITY;
    loadAll({ city: DEFAULT_CITY });
});
