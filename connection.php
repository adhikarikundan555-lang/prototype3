<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$serverName = "localhost";
$userName = "root";
$password = "";
$dbName = "prototype2";

$conn = mysqli_connect($serverName, $userName, $password, $dbName);

// Create DB if not exists
if (!$conn) {
    $conn = mysqli_connect($serverName, $userName, $password);
    mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $dbName");
    mysqli_select_db($conn, $dbName);
}

// Create table
$createTable = "
CREATE TABLE IF NOT EXISTS weather (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100),
    temperature FLOAT,
    main VARCHAR(100),
    weather_condition VARCHAR(100),
    weather_icon VARCHAR(20),
    humidity FLOAT,
    wind FLOAT,
    pressure FLOAT,
    timestamp DATETIME
)";
mysqli_query($conn, $createTable);

// Get city
$cityName = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : "Kathmandu";

// Check cached data (2 hours)
$check = "
SELECT * FROM weather 
WHERE city='$cityName' 
AND timestamp > DATE_SUB(NOW(), INTERVAL 2 HOUR) 
LIMIT 1
";
$result = mysqli_query($conn, $check);

$rows = [];

if (mysqli_num_rows($result) > 0) {

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

} else {

    // OpenWeather API
    $API_KEY = "210bdc4ba7105929b048ac0d0d68c5c7";
    $url = "https://api.openweathermap.org/data/2.5/weather?q=$cityName&units=metric&appid=$API_KEY";

    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(["error" => true]);
        exit;
    }

    $data = json_decode($response, true);

    if ($data["cod"] != 200) {
        echo json_encode(["error" => true]);
        exit;
    }

    $temperature = $data["main"]["temp"];
    $humidity = $data["main"]["humidity"];
    $pressure = $data["main"]["pressure"];
    $wind = $data["wind"]["speed"];
    $main = $data["weather"][0]["main"];
    $weather_condition = $data["weather"][0]["description"];
    $weather_icon = $data["weather"][0]["icon"];
    $timestamp = date("Y-m-d H:i:s");

    // Remove old city data
    mysqli_query($conn, "DELETE FROM weather WHERE city='$cityName'");

    // Insert new data
    $insert = "
    INSERT INTO weather 
    (city, temperature, main, weather_condition, weather_icon, humidity, wind, pressure, timestamp)
    VALUES 
    ('$cityName', '$temperature', '$main', '$weather_condition', '$weather_icon', '$humidity', '$wind', '$pressure', '$timestamp')
    ";
    mysqli_query($conn, $insert);

    // Fetch inserted data
    $result = mysqli_query($conn, "SELECT * FROM weather WHERE city='$cityName' LIMIT 1");
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
}

echo json_encode($rows);
mysqli_close($conn);
?>
