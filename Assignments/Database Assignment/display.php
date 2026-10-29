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

$selectedNode = $_GET["node"] ?? "node_1";
$safeNode = $conn->real_escape_string($selectedNode);

$nodes = $conn->query("
    SELECT node_name
    FROM sensor_register
    ORDER BY node_name
");

$stats = $conn->query("
    SELECT
        AVG(temperature) AS average_temperature,
        AVG(humidity) AS average_humidity,
        MAX(temperature) AS maximum_temperature,
        MIN(humidity) AS minimum_humidity
    FROM sensor_data
    WHERE node_name = '$safeNode'
")->fetch_assoc();

$data = $conn->query("
    SELECT time_received, temperature
    FROM sensor_data
    WHERE node_name = '$safeNode'
    ORDER BY time_received
");

$times = [];
$temperatures = [];

$jsonData = $conn->query("
    SELECT node_name, time_received, temperature, humidity
    FROM sensor_data
    WHERE node_name = '$safeNode'
");

$jsonRows = [];

while ($row = $jsonData->fetch_assoc()) {
    $jsonRows[] = $row;
}

while ($row = $data->fetch_assoc()) {
    $times[] = $row["time_received"];
    $temperatures[] = (float)$row["temperature"];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sensor Data</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial;
            text-align: center;
        }

        table {
            width: 1200px;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
        }

        th {
            background: #dddddd;
        }

        .stats {
            margin: 25px auto;
        }

        .chart {
            width: 1000px;
            max-width: 90%;
            margin: 25px auto;
        }
    </style>
</head>

<body>

<h1>Sensor Data</h1>

<h2>Sensor Data Table</h2>

<table>
    <tr>
        <th>Node Name</th>
        <th>Time Received</th>
        <th>Temperature</th>
        <th>Humidity</th>
    </tr>

    <?php
    $rows = $conn->query("
        SELECT node_name, time_received, temperature, humidity
        FROM sensor_data
        ORDER BY node_name, time_received
    ");

    while ($row = $rows->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["node_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["time_received"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["temperature"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["humidity"]) . "</td>";
        echo "</tr>";
    }
    ?>
</table>

<h2>Sensor Register Table</h2>

<table>
    <tr>
        <th>Node Name</th>
        <th>Manufacturer</th>
        <th>Longitude</th>
        <th>Latitude</th>
    </tr>

    <?php
    $register = $conn->query("
        SELECT node_name, manufacturer, longitude, latitude
        FROM sensor_register
        ORDER BY node_name
    ");

    while ($row = $register->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["node_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["manufacturer"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["longitude"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["latitude"]) . "</td>";
        echo "</tr>";
    }
    ?>
</table>

<form method="get">
    <label>Select a sensor:</label>

    <select name="node" onchange="this.form.submit()">
        <?php while ($node = $nodes->fetch_assoc()) { ?>
            <option value="<?php echo htmlspecialchars($node["node_name"]); ?>"
                <?php if ($node["node_name"] == $selectedNode) echo "selected"; ?>>
                <?php echo htmlspecialchars($node["node_name"]); ?>
            </option>
        <?php } ?>
    </select>
</form>

<div class="stats">
    <h2>Statistics for <?php echo htmlspecialchars($selectedNode); ?></h2>

    <p>Average Temperature:
        <?php echo round($stats["average_temperature"], 2); ?>
    </p>

    <p>Average Humidity:
        <?php echo round($stats["average_humidity"], 2); ?>
    </p>

    <p>Maximum Temperature:
        <?php echo round($stats["maximum_temperature"], 2); ?>
    </p>

    <p>Minimum Humidity:
        <?php echo round($stats["minimum_humidity"], 2); ?>
    </p>
</div>

<div class="chart">
    <canvas id="temperatureChart"></canvas>
</div>

<h2>JSON Data</h2>

<pre><?php echo json_encode($jsonRows, JSON_PRETTY_PRINT); ?></pre>

<script>
new Chart(document.getElementById("temperatureChart"), {
    type: "line",

    data: {
        labels: <?php echo json_encode($times); ?>,

        datasets: [{
            label: "Temperature",
            data: <?php echo json_encode($temperatures); ?>,
            backgroundColor: "rgba(0, 128, 0, 0.6)"
        }]
    },

    options: {
        responsive: true,

        plugins: {
            title: {
                display: true,
                text: "Temperature for <?php echo htmlspecialchars($selectedNode); ?>"
            }
        },

        scales: {
            x: {
                title: {
                    display: true,
                    text: "Time"
                }
            },

            y: {
                title: {
                    display: true,
                    text: "Temperature"
                }
            }
        }
    }
});
</script>

</body>
</html>
