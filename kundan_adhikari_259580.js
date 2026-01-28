const dateElem = document.getElementById("date");
const searchbox = document.querySelector(".search input");
const searchBtn = document.querySelector(".search button");
const weatherIcon = document.querySelector(".weather-icon");

const date = new Date();
dateElem.textContent = date.toDateString();

async function checkweather(city) {
    if (!city) {
        alert("Please enter a city name");
        return;
    }

    let data;

    try {
        // ✅ STEP 1: CHECK ONLINE / OFFLINE
        if (navigator.onLine) {
            // 🌐 ONLINE → fetch from API
            const response = await fetch(
                `http://localhost/Prototype2/connection.php?q=${city}`
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            data = await response.json();

            // ✅ STEP 2: SAVE TO LOCAL STORAGE
            localStorage.setItem(city, JSON.stringify(data));
        } else {
            // 📦 OFFLINE → load from localStorage
            const cachedData = localStorage.getItem(city);

            if (!cachedData) {
                alert("No cached data available for this city.");
                return;
            }

            // ✅ STEP 3: READ FROM LOCAL STORAGE
            data = JSON.parse(cachedData);
        }

        console.log("Weather data used:", data);

        if (!data || data.length === 0) {
            alert("City not found! Please try another city.");
            return;
        }

        const weather = data[0];

        if (weather.city === undefined || weather.temperature === undefined) {
            alert("Invalid weather data received");
            return;
        }

        // ✅ STEP 5 & 6: UPDATE UI (UNCHANGED)
        document.querySelector(".city").textContent = weather.city;
        document.querySelector(".temp").textContent =
            Math.round(weather.temperature) + "°C";
        document.querySelector(".humidity").textContent =
            weather.humidity + "%";
        document.querySelector(".pressure").textContent =
            weather.pressure + " hPa";
        document.querySelector(".wind").textContent =
            (weather.wind * 3.6).toFixed(1) + " km/h";

        weatherIcon.src =
            `https://openweathermap.org/img/wn/${weather.weather_icon}@2x.png`;

    } catch (error) {
        console.error("Fetch error:", error);
        alert("Error fetching weather data: " + error.message);
    }
}

searchBtn.addEventListener("click", () => {
    checkweather(searchbox.value.trim());
});

searchbox.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        checkweather(searchbox.value.trim());
    }
});

checkweather("Aberdeen");
