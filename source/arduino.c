
#define PIN_X    9
#define PIN_Y    3
#define PIN_LDR  A0

void setup()
{
  pinMode(PIN_X, OUTPUT);
  pinMode(PIN_Y, OUTPUT);
  pinMode(PIN_LDR, INPUT);

  Serial.begin(115200);
  delay(100);
  Serial.println("Jesus Amor");
}

int delayTime = 23;
int limit     = 256;
int increment = 1;

void loop()
{
  if (Serial.available() > 0)
  {
    String command = Serial.readStringUntil('\n');
    command.trim();
    char receivedChars[256];
    memset(receivedChars, '\0', 256);
    command.toCharArray(receivedChars, 256);

    // Read line.
    if (strstr(receivedChars, "read:"))
    {
      char *ptr = &receivedChars[ strlen("read:") ];
      char currentYCh[256];
      int currentY = 0;

      memset(currentYCh, '\0', 256);

      for (int a=0; ptr[a]!='\0' && ptr[a]!='\n'; a++)
      {
        if (ptr[a] == '-')
        {
          currentY = atoi(currentYCh);
          break;
        }
        
        currentYCh[a] = ptr[a];
      }

      // Control: X, Y.
      Serial.print("startline");
      analogWrite(PIN_Y, currentY);
      delay(50);
      
      for (int x=0; x<limit; x+=increment)
      {
        analogWrite(PIN_X, x);
        delay(delayTime);
        
        int LDR = analogRead(PIN_LDR) / 4;
        Serial.print(LDR, DEC);
        Serial.print(",");
      }
      
      Serial.print("endline");
      delay(100);
    }
  }
}


