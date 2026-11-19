#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <PubSubClient.h>

const char* WIFI_SSID = "RachelComputer";
const char* WIFI_PASSWORD = "";

const char* MQTT_BROKER = "broker.hivemq.com";
const int MQTT_PORT = 1883;

const char* IN_TOPIC = "testtopic/temp/inTopic";
const char* OUT_TOPIC = "testtopic/temp/outTopic/rachel7163";

const int POT_PIN = A0;
const int LED_PIN = D5;
const int SWITCH_PIN = D6;

WiFiClient espClient;
PubSubClient mqttClient(espClient);

bool sendZeroLater = false;
unsigned long zeroSendTime = 0;

void connectToWiFi() {
  Serial.print("Connecting to Wi-Fi");

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.println("Wi-Fi connected");
  Serial.println(WiFi.localIP());
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message = "";

  for (unsigned int i = 0; i < length; i++) {
    message += (char)payload[i];
  }

  Serial.print("Received ");
  Serial.print(message);
  Serial.print(" on ");
  Serial.println(topic);

  if (String(topic) == IN_TOPIC) {
    if (message == "1") {
      digitalWrite(LED_PIN, HIGH);
      Serial.println("LED ON");
    }

    if (message == "0") {
      digitalWrite(LED_PIN, LOW);
      Serial.println("LED OFF");
    }
  }
}

void connectToMQTT() {
  while (!mqttClient.connected()) {
    Serial.print("Connecting to MQTT broker...");

    String clientID = "ESP8266-Rachel-";
    clientID += String(random(0xffff), HEX);

    if (mqttClient.connect(clientID.c_str())) {
      Serial.println("connected");

      mqttClient.subscribe(IN_TOPIC);
      Serial.print("Subscribed to: ");
      Serial.println(IN_TOPIC);
    } else {
      Serial.print("failed, error code: ");
      Serial.println(mqttClient.state());
      delay(5000);
    }
  }
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  pinMode(LED_PIN, OUTPUT);
  pinMode(SWITCH_PIN, INPUT_PULLUP);

  digitalWrite(LED_PIN, LOW);

  randomSeed(micros());

  connectToWiFi();

  mqttClient.setServer(MQTT_BROKER, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
}

void loop() {
  if (!mqttClient.connected()) {
    connectToMQTT();
  }

  mqttClient.loop();

  // Publish the potentiometer value every 15 seconds
  static unsigned long lastPublish = 0;

  if (millis() - lastPublish >= 15000) {
    lastPublish = millis();

    int potentiometerValue = analogRead(POT_PIN);

    char message[10];
    snprintf(message, sizeof(message), "%d", potentiometerValue);

    mqttClient.publish(OUT_TOPIC, message);

    Serial.print("Published ");
    Serial.println(message);
  }

  // Detect when the switch is released
  static int previousSwitchState = HIGH;
  int currentSwitchState = digitalRead(SWITCH_PIN);

  if (previousSwitchState == LOW && currentSwitchState == HIGH) {
    mqttClient.publish(OUT_TOPIC, "1");

    Serial.println("Switch released: published 1");

    sendZeroLater = true;
    zeroSendTime = millis() + 5000;
  }

  previousSwitchState = currentSwitchState;

  // Publish 0 five seconds later
  if (sendZeroLater && millis() >= zeroSendTime) {
    mqttClient.publish(OUT_TOPIC, "0");

    Serial.println("Published 0 after 5 seconds");

    sendZeroLater = false;
  }
}
