<?php
$conn = new mysqli(
    "localhost",
    "u558555076_db_RachelM",
    "*mypassword*",
    "u558555076_RachelMarkman"
);

if ($conn->connect_error) {
    die("Database connection failed");
}

$encoded = $_SERVER["QUERY_STRING"];
$decoded = base64_decode($encoded);
parse_str($decoded, $data);

$nodeId = $data["nodeId"] ?? "";
$nodeTemp = $data["nodeTemp"] ?? "";
$timeReceived = $data["timeReceived"] ?? "";
$nodeHumidity = $data["nodeHumidity"] ?? 0;

if ($nodeId == "" || $nodeTemp == "" || $timeReceived == "") {
    die("Missing required information");
}

$stmt = $conn->prepare("
    INSERT INTO sensor_data
    (node_name, time_received, temperature, humidity)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "ssdd",
    $nodeId,
    $timeReceived,
    $nodeTemp,
    $nodeHumidity
);

if ($stmt->execute()) {
    echo "<h2>Data inserted successfully</h2>";
    echo "Decoded node ID: " . htmlspecialchars($nodeId) . "<br>";
    echo "Decoded temperature: " . htmlspecialchars($nodeTemp) . "<br>";
    echo "Decoded time: " . htmlspecialchars($timeReceived) . "<br>";
    echo "Decoded humidity: " . htmlspecialchars($nodeHumidity) . "<br>";
} else {
    echo "Error inserting data";
}
?>
