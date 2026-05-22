# Weather Dashboard

A responsive single-page weather dashboard built with **HTML**, **CSS**, **JavaScript**, and **PHP**. Users can search by city or use their current location to view real-time weather and a 5-day forecast.

The OpenWeather API key is stored in `.env` and used only on the server — it is never exposed in the browser.

## Features

- Search weather by city name (e.g. Chennai, Mumbai, Delhi, Bangalore)
- Current location weather (browser geolocation)
- 5-day forecast
- Loading states and error handling (invalid city, API failures, missing API key)
- Search on Enter key
- Mobile-responsive UI (Bootstrap 5 + custom CSS)
- Secure API key via `.env` and PHP proxy endpoints

## Tech Stack

| Layer      | Technology                          |
|-----------|--------------------------------------|
| Frontend  | HTML, CSS, JavaScript, Bootstrap 5  |
| Backend   | PHP (Core)                          |
| API       | [OpenWeather API](https://openweathermap.org/api) |

## Project Structure

```
weather-dashboard/
├── .env                 # Your API key (not committed to git)
├── .env.example         # Template for .env
├── index.php            # Main dashboard page
├── style.css            # Custom styles
├── script.js            # Frontend logic (calls PHP API)
├── api/
│   ├── weather.php      # Current weather proxy
│   └── forecast.php     # 5-day forecast proxy
├── config/
│   ├── env.php          # Loads .env variables
│   └── bootstrap.php    # Shared API helpers
└── README.md
```

## Setup Instructions

### 1. Install XAMPP

Download and install [XAMPP](https://www.apachefriends.org/) for Windows.

### 2. Place the project in `htdocs`

The project should live at:

```
C:\xampp\htdocs\weather-dashboard
```

### 3. Configure the API key

1. Create a free account at [OpenWeather](https://openweathermap.org/api).
2. Generate an API key from your account dashboard.
3. Copy `.env.example` to `.env` (if `.env` does not exist).
4. Open `.env` and set your key:

```env
OPENWEATHER_API_KEY=your_actual_api_key_here
```

> **Note:** New API keys can take up to 2 hours to activate on OpenWeather’s free tier.

### 4. Start Apache

1. Open **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.

### 5. Open in the browser

Visit:

```
http://localhost/weather-dashboard/
```

## API Key Configuration (`.env`)

| Variable               | Description                    |
|------------------------|--------------------------------|
| `OPENWEATHER_API_KEY`  | Your OpenWeather API key       |

PHP reads this value in `config/env.php`. The frontend calls local endpoints (`api/weather.php`, `api/forecast.php`) which attach the key server-side.

**Do not** put the API key in `script.js` or commit `.env` to version control. `.gitignore` already excludes `.env`.

## Testing

Try searching for:

- Chennai
- Mumbai
- Delhi
- Bangalore

Also test:

- Empty search (validation message)
- Invalid city name (e.g. `XyzInvalidCity123`)
- **Use Current Location** (allow browser permission when prompted)

## Troubleshooting

| Issue | Solution |
|-------|----------|
| API key not configured | Add `OPENWEATHER_API_KEY` to `.env` |
| Invalid API key (401) | Verify the key in OpenWeather dashboard |
| City not found | Check spelling; try `City,Country` format |
| Blank page / 404 | Confirm Apache is running and the folder path is correct |
| CORS / fetch errors | Use `http://localhost/weather-dashboard/` (not `file://`) |

## License

Educational / assignment project. OpenWeather data is subject to [their terms](https://openweathermap.org/terms).
