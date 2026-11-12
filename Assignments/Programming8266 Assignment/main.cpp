/*
 * ----------------------------------------------
 * Project/Program Name : ESP8266 IoT Sensor System
 * File Name            : main.cpp
 * Author               : Rachel Markman
 * Date                 : 12/11/2026
 * Version              : 1.0
 *
 * Purpose:
 * Read DHT11 and HW-486 light sensor data and transmit it
 * to a Hostinger database when a switch is activated.
 *
 * Inputs:
 * DHT11, HW-486 photoresistor, pushbutton, tilt sensor, and WiFi.
 *
 * Outputs:
 * Sensor data, timestamp, RSSI, IP address, and MAC address.
 *
 * ----------------------------------------------
 */

#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <DHT.h>
#include <ArduinoJson.h>

const char* WIFI_SSID = "Alex";
const char* WIFI_PASSWORD = "";

const char* SERVER_URL =
    "https://rachelmarkman.com/insert.php";

#define DHT_PIN D5
#define BUTTON_PIN D6
#define TILT_PIN D7
#define LIGHT_PIN A0

#define DHT_TYPE DHT11

DHT dht(DHT_PIN, DHT_TYPE);

String selectedTimeZone = "America/Los_Angeles";

bool previousButtonState = false;
bool previousTiltState = false;

struct Measurement {
    bool active;
    String nodeId;
    float temperature;
    float humidity;
    float lightLevel;
    String timestamp;
    unsigned long lastAttempt;
};

Measurement pendingMeasurement = {
    false, "", 0, 0, 0, "", 0
};

void connectToWiFi();
void selectTimeZone();
String getCurrentTime();
float readLightLevel();
void checkSwitches();
void createMeasurement(String nodeId);
bool sendMeasurement(Measurement& measurement);
void retryPendingMeasurement();
String base64Encode(String input);
String encodeBase64ForURL(String input);

void setup() {
    Serial.begin(115200);
    delay(1000);

    pinMode(BUTTON_PIN, INPUT_PULLUP);
    pinMode(TILT_PIN, INPUT_PULLUP);

    dht.begin();

    Serial.println();
    Serial.println("ESP8266 IoT Sensor System");

    selectTimeZone();
    connectToWiFi();

    previousButtonState = digitalRead(BUTTON_PIN) == LOW;
    previousTiltState = digitalRead(TILT_PIN) == LOW;

    Serial.println("System ready.");
}

void loop() {
    checkSwitches();
    retryPendingMeasurement();

    delay(20);
}

void connectToWiFi() {
    Serial.print("Connecting to WiFi");

    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    unsigned long startTime = millis();

    while (
        WiFi.status() != WL_CONNECTED &&
        millis() - startTime < 20000
    ) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("WiFi connected.");

        Serial.print("IP Address: ");
        Serial.println(WiFi.localIP());

        Serial.print("MAC Address: ");
        Serial.println(WiFi.macAddress());

        Serial.print("RSSI: ");
        Serial.print(WiFi.RSSI());
        Serial.println(" dBm");
    } else {
        Serial.println("WiFi connection failed.");
    }
}

void selectTimeZone() {
    Serial.println();
    Serial.println("Select Your Time Zone:");
    Serial.println("1. Eastern Time");
    Serial.println("2. Central Time");
    Serial.println("3. Mountain Time");
    Serial.println("4. Pacific Time");
    Serial.println("5. Alaska Time");
    Serial.println("6. Hawaii-Aleutian Time");
    Serial.println("7. Atlantic Time");
    Serial.println("Default: Pacific Time");
    Serial.println("Enter a number:");

    unsigned long startTime = millis();

    while (millis() - startTime < 15000) {
        if (Serial.available()) {
            String input = Serial.readStringUntil('\n');
            input.trim();

            int choice = input.toInt();

            switch (choice) {
                case 1:
                    selectedTimeZone = "America/New_York";
                    break;

                case 2:
                    selectedTimeZone = "America/Chicago";
                    break;

                case 3:
                    selectedTimeZone = "America/Denver";
                    break;

                case 4:
                    selectedTimeZone = "America/Los_Angeles";
                    break;

                case 5:
                    selectedTimeZone = "America/Anchorage";
                    break;

                case 6:
                    selectedTimeZone = "Pacific/Honolulu";
                    break;

                case 7:
                    selectedTimeZone = "America/Puerto_Rico";
                    break;

                default:
                    selectedTimeZone = "America/Los_Angeles";
                    break;
            }

            Serial.print("Selected time zone: ");
            Serial.println(selectedTimeZone);

            return;
        }

        delay(50);
    }

    selectedTimeZone = "America/Los_Angeles";

    Serial.println("No selection entered.");
    Serial.println("Using Pacific Time.");
}

String getCurrentTime() {
    if (WiFi.status() != WL_CONNECTED) {
        return "";
    }

    WiFiClientSecure secureClient;
    secureClient.setInsecure();

    HTTPClient https;

    String url =
        "https://timeapi.io/api/Time/current/zone?timeZone="
        + selectedTimeZone;

    if (!https.begin(secureClient, url)) {
        return "";
    }

    int httpCode = https.GET();

    if (httpCode != HTTP_CODE_OK) {
        https.end();
        return "";
    }

    String response = https.getString();
    https.end();

    DynamicJsonDocument document(2048);

    DeserializationError error =
        deserializeJson(document, response);

    if (error) {
        return "";
    }

    String timestamp = document["dateTime"].as<String>();

    if (timestamp.length() < 19) {
        return "";
    }

    timestamp = timestamp.substring(0, 19);
    timestamp.replace("T", " ");

    return timestamp;
}

float readLightLevel() {
    long total = 0;

    for (int i = 0; i < 10; i++) {
        total += analogRead(LIGHT_PIN);
        delay(5);
    }

    return total / 10.0;
}

void checkSwitches() {
    bool buttonPressed = digitalRead(BUTTON_PIN) == LOW;
    bool tiltActivated = digitalRead(TILT_PIN) == LOW;

    if (buttonPressed && !previousButtonState) {
        Serial.println("Pushbutton activated.");
        createMeasurement("node_1");
    }

    if (tiltActivated && !previousTiltState) {
        Serial.println("Tilt sensor activated.");
        createMeasurement("node_2");
    }

    previousButtonState = buttonPressed;
    previousTiltState = tiltActivated;
}

void createMeasurement(String nodeId) {
    if (pendingMeasurement.active) {
        Serial.println(
            "Waiting for the previous transmission to succeed."
        );

        return;
    }

    float temperature = dht.readTemperature();
    float humidity = dht.readHumidity();
    float lightLevel = readLightLevel();
    String timestamp = getCurrentTime();

    if (isnan(temperature) || isnan(humidity)) {
        Serial.println("ERROR: Could not read the DHT11.");
        return;
    }

    if (timestamp == "") {
        Serial.println("ERROR: Could not obtain the timestamp.");
        return;
    }

    pendingMeasurement.active = true;
    pendingMeasurement.nodeId = nodeId;
    pendingMeasurement.temperature = temperature;
    pendingMeasurement.humidity = humidity;
    pendingMeasurement.lightLevel = lightLevel;
    pendingMeasurement.timestamp = timestamp;
    pendingMeasurement.lastAttempt = millis();

    Serial.println("New measurement:");

    Serial.print("Node: ");
    Serial.println(nodeId);

    Serial.print("Temperature: ");
    Serial.println(temperature);

    Serial.print("Humidity: ");
    Serial.println(humidity);

    Serial.print("Light Level: ");
    Serial.println(lightLevel);

    Serial.print("Timestamp: ");
    Serial.println(timestamp);

    if (sendMeasurement(pendingMeasurement)) {
        pendingMeasurement.active = false;
    } else {
        Serial.println("Transmission failed.");
        Serial.println("Will retry in 30 seconds.");
    }
}

bool sendMeasurement(Measurement& measurement) {
    if (WiFi.status() != WL_CONNECTED) {
        connectToWiFi();
    }

    if (WiFi.status() != WL_CONNECTED) {
        return false;
    }

    String payload =
        "nodeId=" + measurement.nodeId +
        "&nodeTemp=" +
        String(measurement.temperature, 2) +
        "&nodeHumidity=" +
        String(measurement.humidity, 2) +
        "&nodeLight=" +
        String(measurement.lightLevel, 2) +
        "&timeReceived=" +
        measurement.timestamp;

    String encodedPayload =
        encodeBase64ForURL(base64Encode(payload));

    String url = String(SERVER_URL) + "?" + encodedPayload;

    WiFiClientSecure secureClient;
    secureClient.setInsecure();

    HTTPClient https;

    if (!https.begin(secureClient, url)) {
        return false;
    }

    int httpCode = https.GET();
    String response = https.getString();

    https.end();

    Serial.print("HTTP response code: ");
    Serial.println(httpCode);

    Serial.print("Server response: ");
    Serial.println(response);

    if (
        httpCode == HTTP_CODE_OK &&
        (
            response.indexOf("SUCCESS") >= 0 ||
            response.indexOf("DUPLICATE") >= 0
        )
    ) {
        Serial.println("Transmission successful.");
        return true;
    }

    return false;
}

void retryPendingMeasurement() {
    if (!pendingMeasurement.active) {
        return;
    }

    if (
        millis() - pendingMeasurement.lastAttempt < 30000
    ) {
        return;
    }

    Serial.println("Retrying transmission...");

    pendingMeasurement.lastAttempt = millis();

    if (sendMeasurement(pendingMeasurement)) {
        pendingMeasurement.active = false;
        Serial.println("Retransmission successful.");
    } else {
        Serial.println("Retransmission failed.");
    }
}

String base64Encode(String input) {
    const char* table =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
        "abcdefghijklmnopqrstuvwxyz"
        "0123456789+/";

    String output;
    int value = 0;
    int bits = -6;

    for (int i = 0; i < input.length(); i++) {
        unsigned char character = input[i];

        value = (value << 8) + character;
        bits += 8;

        while (bits >= 0) {
            output += table[(value >> bits) & 0x3F];
            bits -= 6;
        }
    }

    if (bits > -6) {
        output += table[
            ((value << 8) >> (bits + 8)) & 0x3F
        ];
    }

    while (output.length() % 4) {
        output += '=';
    }

    return output;
}

String encodeBase64ForURL(String input) {
    input.replace("+", "%2B");
    input.replace("/", "%2F");
    input.replace("=", "%3D");

    return input;
}
