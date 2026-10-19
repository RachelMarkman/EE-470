
-- EE 470 Database Assignment - Part 1
-- Section 1.3: Sensor Database
-- Rachel Markman

-- Create sensor registration table
CREATE TABLE sensor_register (
    node_name VARCHAR(10) PRIMARY KEY,
    manufacturer VARCHAR(10) NOT NULL,
    longitude DECIMAL(15,10),
    latitude DECIMAL(15,10)
);

-- Create sensor data table
CREATE TABLE sensor_data (
    node_name VARCHAR(10) NOT NULL,
    time_received DATETIME NOT NULL,
    temperature DECIMAL(6,2) NOT NULL,
    humidity DECIMAL(6,2) NOT NULL,
    PRIMARY KEY (node_name, time_received),
    FOREIGN KEY (node_name)
        REFERENCES sensor_register(node_name),
    CHECK (temperature BETWEEN -10 AND 100),
    CHECK (humidity BETWEEN 0 AND 100)
);

-- Register five sensors
INSERT INTO sensor_register
(node_name, manufacturer, longitude, latitude)
VALUES
('node_1', 'Bosch', -122.4350000000, 38.4400000000),
('node_2', 'Honeywell', -122.4400000000, 38.4450000000),
('node_3', 'Siemens', -122.4450000000, 38.4500000000),
('node_4', 'Sensirion', -122.4500000000, 38.4550000000),
('node_5', 'Omron', -122.4550000000, 38.4600000000);

-- Insert four readings per sensor
-- Starting October 1, 2022 at 11:00 AM
-- Readings every 30 minutes
INSERT INTO sensor_data
(node_name, time_received, temperature, humidity)
VALUES
('node_1', '2022-10-01 11:00:00', 22, 45),
('node_1', '2022-10-01 11:30:00', 23, 46),
('node_1', '2022-10-01 12:00:00', 24, 47),
('node_1', '2022-10-01 12:30:00', 25, 48),

('node_2', '2022-10-01 11:00:00', 26, 50),
('node_2', '2022-10-01 11:30:00', 27, 51),
('node_2', '2022-10-01 12:00:00', 28, 52),
('node_2', '2022-10-01 12:30:00', 29, 53),

('node_3', '2022-10-01 11:00:00', 20, 55),
('node_3', '2022-10-01 11:30:00', 21, 56),
('node_3', '2022-10-01 12:00:00', 22, 57),
('node_3', '2022-10-01 12:30:00', 23, 58),

('node_4', '2022-10-01 11:00:00', 30, 40),
('node_4', '2022-10-01 11:30:00', 31, 41),
('node_4', '2022-10-01 12:00:00', 32, 42),
('node_4', '2022-10-01 12:30:00', 33, 43),

('node_5', '2022-10-01 11:00:00', 18, 60),
('node_5', '2022-10-01 11:30:00', 19, 61),
('node_5', '2022-10-01 12:00:00', 20, 62),
('node_5', '2022-10-01 12:30:00', 21, 63);

-- Create combined VIEW
CREATE VIEW sensor_combined AS
SELECT
    sensor_data.node_name,
    sensor_data.time_received,
    sensor_data.temperature,
    sensor_data.humidity,
    sensor_register.manufacturer,
    sensor_register.longitude,
    sensor_register.latitude
FROM sensor_data
INNER JOIN sensor_register
ON sensor_data.node_name = sensor_register.node_name;

-- Display all combined sensor data
SELECT * FROM sensor_combined
ORDER BY node_name, time_received;

-- Test unregistered sensor rejection
-- This command should fail due to foreign key constraint
-- Uncomment to test separately
-- INSERT INTO sensor_data
-- (node_name, time_received, temperature, humidity)
-- VALUES
-- ('node_6', '2022-10-01 13:00:00', 25, 50);
