<?php

$conn = new mysqli(
    "localhost",
    "u558555076_db_RachelM",
    "",
    "u558555076_RachelMarkman"
);

if ($conn->connect_error) {
    die("ERROR: Database connection failed");
}

$encoded = urldecode($_SERVER["QUERY_STRING"]);
$decoded = base64_decode($encoded, true);

if ($decoded === false) {
    die("ERROR: Invalid encoded data");
}

parse_str($decoded, $data);

$nodeId = trim($data["nodeId"] ?? "");
$nodeTemp = $data["nodeTemp"] ?? "";
$nodeHumidity = $data["nodeHumidity"] ?? "";
$nodeLight = $data["nodeLight"] ?? "";
$timeReceived = trim($data["timeReceived"] ?? "");

$timeReceived = str_replace("T", " ", $timeReceived);
$timeReceived = substr($timeReceived, 0, 19);

if (
    $nodeId === "" ||
    $nodeTemp === "" ||
    $nodeHumidity === "" ||
    $nodeLight === "" ||
    $timeReceived === ""
) {
    die("ERROR: Missing required information");
}

if (
    !is_numeric($nodeTemp) ||
    !is_numeric($nodeHumidity) ||
    !is_numeric($nodeLight)
) {
    die("ERROR: Sensor values must be numeric");
}

$dateCheck = DateTime::createFromFormat(
    "Y-m-d H:i:s",
    $timeReceived
);

if (
    !$dateCheck ||
    $dateCheck->format("Y-m-d H:i:s") !== $timeReceived
) {
    die("ERROR: Invalid timestamp format");
}

$registerCheck = $conn->prepare("
    SELECT node_name
    FROM sensor_register
    WHERE node_name = ?
    LIMIT 1
");

$registerCheck->bind_param("s", $nodeId);
$registerCheck->execute();
$registerCheck->store_result();

if ($registerCheck->num_rows === 0) {
    die("ERROR: Node is not registered");
}

$registerCheck->close();

$stmt = $conn->prepare("
    INSERT INTO sensor_data
    (node_name, time_received, temperature, humidity, light_level)
    VALUES (?, ?, ?, ?, ?)
");

$temp = (float)$nodeTemp;
$humidity = (float)$nodeHumidity;
$light = (float)$nodeLight;

$stmt->bind_param(
    "ssddd",
    $nodeId,
    $timeReceived,
    $temp,
    $humidity,
    $light
);

if (!$stmt->execute()) {
    if ($stmt->errno == 1062) {
        echo "DUPLICATE: Data was already stored";
    } else {
        echo "ERROR: Data was not inserted";
    }

    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

$activity = $conn->prepare("
    UPDATE sensor_activity
    SET valid_count = valid_count + 1
    WHERE node_name = ?
");

$activity->bind_param("s", $nodeId);
$activity->execute();

if ($activity->affected_rows === 0) {
    echo "ERROR: Activity counter was not updated";
    $activity->close();
    $conn->close();
    exit;
}

$activity->close();
$conn->close();

echo "SUCCESS: Data inserted and activity counter updated";

?>
