<?php

$conn = new mysqli(
    "localhost",
    "u558555076_db_RachelM",
    "",
    "u558555076_RachelMarkman"
);

if ($conn->connect_error) {
    die("Database connection failed");
}

$selectedNode = $_GET["node"] ?? "node_1";
$safeNode = $conn->real_escape_string($selectedNode);

function showNumber($value)
{
    if ($value === null) {
        return "N/A";
    }

    return number_format((float)$value, 2);
}

$nodes = $conn->query("
    SELECT node_name
    FROM sensor_register
    ORDER BY node_name
");

$stats = $conn->query("
    SELECT
        COUNT(*) AS measurement_count,
        AVG(temperature) AS average_temperature,
        AVG(humidity) AS average_humidity,
        AVG(light_level) AS average_light,
        MIN(temperature) AS minimum_temperature,
        MAX(temperature) AS maximum_temperature,
        MIN(humidity) AS minimum_humidity,
        MAX(humidity) AS maximum_humidity,
        MIN(light_level) AS minimum_light,
        MAX(light_level) AS maximum_light
    FROM sensor_data
    WHERE node_name = '$safeNode'
")->fetch_assoc();

$data = $conn->query("
    SELECT time_received, temperature, humidity, light_level
    FROM sensor_data
    WHERE node_name = '$safeNode'
    ORDER BY time_received
");

$times = [];
$temperatures = [];
$humidities = [];
$lightLevels = [];

while ($row = $data->fetch_assoc()) {
    $times[] = $row["time_received"];
    $temperatures[] = (float)$row["temperature"];
    $humidities[] = (float)$row["humidity"];
    $lightLevels[] = (float)$row["light_level"];
}

$jsonData = $conn->query("
    SELECT node_name, time_received, temperature, humidity, light_level
    FROM sensor_data
    WHERE node_name = '$safeNode'
    ORDER BY time_received
");

$jsonRows = [];

while ($row = $jsonData->fetch_assoc()) {
    $jsonRows[] = $row;
}

$allRows = $conn->query("
    SELECT node_name, time_received, temperature, humidity, light_level
    FROM sensor_data
    ORDER BY node_name, time_received
");

$registerRows = $conn->query("
    SELECT node_name, manufacturer, longitude, latitude
    FROM sensor_register
    ORDER BY node_name
");

$activityRows = $conn->query("
    SELECT node_name, valid_count
    FROM sensor_activity
    ORDER BY node_name
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ESP8266 Sensor Data</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1,
        h2 {
            color: #222;
        }

        table {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: white;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
        }

        th {
            background-color: #dddddd;
        }

        .selector,
        .stats,
        .chart,
        .json {
            width: 95%;
            max-width: 1200px;
            margin: 25px auto;
            background-color: white;
            padding: 20px;
            box-sizing: border-box;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .stat-box {
            border: 1px solid #bbbbbb;
            padding: 10px;
            background-color: #f9f9f9;
        }

        select {
            font-size: 16px;
            padding: 6px;
        }

        pre {
            text-align: left;
            overflow-x: auto;
            white-space: pre-wrap;
        }

        @media (max-width: 700px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <h1>ESP8266 Sensor Data</h1>

    <div class="selector">
        <form method="get">
            <label for="node">Select a registered node:</label>

            <select name="node" id="node" onchange="this.form.submit()">
                <?php while ($node = $nodes->fetch_assoc()) { ?>
                    <option
                        value="<?php echo htmlspecialchars($node["node_name"]); ?>"
                        <?php
                        if ($node["node_name"] == $selectedNode) {
                            echo "selected";
                        }
                        ?>>
                        <?php echo htmlspecialchars($node["node_name"]); ?>
                    </option>
                <?php } ?>
            </select>
        </form>
    </div>

    <div class="stats">

        <h2>Statistics for <?php echo htmlspecialchars($selectedNode); ?></h2>

        <div class="stats-grid">

            <div class="stat-box">
                <strong>Valid Measurements</strong><br>
                <?php echo $stats["measurement_count"]; ?>
            </div>

            <div class="stat-box">
                <strong>Average Temperature</strong><br>
                <?php echo showNumber($stats["average_temperature"]); ?>
            </div>

            <div class="stat-box">
                <strong>Minimum Temperature</strong><br>
                <?php echo showNumber($stats["minimum_temperature"]); ?>
            </div>

            <div class="stat-box">
                <strong>Maximum Temperature</strong><br>
                <?php echo showNumber($stats["maximum_temperature"]); ?>
            </div>

            <div class="stat-box">
                <strong>Average Humidity</strong><br>
                <?php echo showNumber($stats["average_humidity"]); ?>
            </div>

            <div class="stat-box">
                <strong>Minimum Humidity</strong><br>
                <?php echo showNumber($stats["minimum_humidity"]); ?>
            </div>

            <div class="stat-box">
                <strong>Maximum Humidity</strong><br>
                <?php echo showNumber($stats["maximum_humidity"]); ?>
            </div>

            <div class="stat-box">
                <strong>Average Light Level</strong><br>
                <?php echo showNumber($stats["average_light"]); ?>
            </div>

            <div class="stat-box">
                <strong>Minimum Light Level</strong><br>
                <?php echo showNumber($stats["minimum_light"]); ?>
            </div>

            <div class="stat-box">
                <strong>Maximum Light Level</strong><br>
                <?php echo showNumber($stats["maximum_light"]); ?>
            </div>

        </div>
    </div>

    <div class="chart">
        <h2>Measurements for <?php echo htmlspecialchars($selectedNode); ?></h2>

        <canvas id="sensorChart"></canvas>
    </div>

    <h2>Sensor Activity</h2>

    <table>
        <tr>
            <th>Node Name</th>
            <th>Valid Unique Measurements</th>
        </tr>

        <?php while ($row = $activityRows->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row["node_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["valid_count"]); ?></td>
            </tr>
        <?php } ?>
    </table>

    <h2>Sensor Data Table</h2>

    <table>
        <tr>
            <th>Node Name</th>
            <th>Time Received</th>
            <th>Temperature</th>
            <th>Humidity</th>
            <th>Light Level</th>
        </tr>

        <?php while ($row = $allRows->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row["node_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["time_received"]); ?></td>
                <td><?php echo htmlspecialchars($row["temperature"]); ?></td>
                <td><?php echo htmlspecialchars($row["humidity"]); ?></td>
                <td><?php echo htmlspecialchars($row["light_level"]); ?></td>
            </tr>
        <?php } ?>
    </table>

    <h2>Sensor Register Table</h2>

    <table>
        <tr>
            <th>Node Name</th>
            <th>Manufacturer</th>
            <th>Longitude</th>
            <th>Latitude</th>
        </tr>

        <?php while ($row = $registerRows->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row["node_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["manufacturer"]); ?></td>
                <td><?php echo htmlspecialchars($row["longitude"]); ?></td>
                <td><?php echo htmlspecialchars($row["latitude"]); ?></td>
            </tr>
        <?php } ?>
    </table>

    <div class="json">
        <h2>JSON Data</h2>
        <pre><?php echo json_encode($jsonRows, JSON_PRETTY_PRINT); ?></pre>
    </div>

    <script>
        new Chart(document.getElementById("sensorChart"), {
            type: "line",

            data: {
                labels: <?php echo json_encode($times); ?>,

                datasets: [
                    {
                        label: "Temperature",
                        data: <?php echo json_encode($temperatures); ?>,
                        borderColor: "red",
                        backgroundColor: "rgba(255, 0, 0, 0.2)",
                        tension: 0.2
                    },
                    {
                        label: "Humidity",
                        data: <?php echo json_encode($humidities); ?>,
                        borderColor: "blue",
                        backgroundColor: "rgba(0, 0, 255, 0.2)",
                        tension: 0.2
                    },
                    {
                        label: "Light Level",
                        data: <?php echo json_encode($lightLevels); ?>,
                        borderColor: "green",
                        backgroundColor: "rgba(0, 128, 0, 0.2)",
                        tension: 0.2
                    }
                ]
            },

            options: {
                responsive: true,

                plugins: {
                    title: {
                        display: true,
                        text: <?php echo json_encode("Sensor Measurements for " . $selectedNode); ?>
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
                            text: "Sensor Values"
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>
