#include <Arduino.h>

const float R1 = 10000.0;
const float R2 = 3900.0;

// Calibration factor from your ADC accuracy test
const float ADC_VOLTS_PER_COUNT = 0.002971;

unsigned long sampleNumber = 0;

void setup() {
  Serial.begin(115200);
  delay(2000);

  Serial.println("Sample,Time_min,ADC,Battery_V");
}

void loop() {
  sampleNumber++;

  int adcValue = analogRead(A0);

  float a0Voltage = adcValue * ADC_VOLTS_PER_COUNT;

  float batteryVoltage =
      a0Voltage * ((R1 + R2) / R2);

  float timeMinutes = sampleNumber - 1;

  Serial.print(sampleNumber);
  Serial.print(",");
  Serial.print(timeMinutes, 0);
  Serial.print(",");
  Serial.print(adcValue);
  Serial.print(",");
  Serial.println(batteryVoltage, 3);

  delay(60000);
}
