<?php

declare(strict_types=1);

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/AlarmRepository.php';
require __DIR__ . '/../src/DeviceMessageHandler.php';
require __DIR__ . '/../src/MqttClient.php';

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}

load_env(__DIR__ . '/../.env');

$handler = new DeviceMessageHandler(new AlarmRepository());
$reconnectDelay = (int) env_value('MQTT_RECONNECT_DELAY', '5');

while (true) {
    $client = new MqttClient(
        env_value('MQTT_HOST', '127.0.0.1'),
        (int) env_value('MQTT_PORT', '1883'),
        env_value('MQTT_CLIENT_ID', 'alarm-monitoring-subscriber'),
        env_value('MQTT_USERNAME') !== '' ? env_value('MQTT_USERNAME') : null,
        env_value('MQTT_PASSWORD') !== '' ? env_value('MQTT_PASSWORD') : null,
        (int) env_value('MQTT_KEEP_ALIVE', '60'),
    );
    $topics = [
        env_value('MQTT_TOPIC_STATE', 'alarm/button/+/state') => 1,
        env_value('MQTT_TOPIC_HEARTBEAT', 'alarm/button/+/heartbeat') => 1,
    ];

    try {
        echo sprintf(
            "[%s] MQTT worker subscribe ke %s:%d\n",
            date('Y-m-d H:i:s'),
            env_value('MQTT_HOST', '127.0.0.1'),
            (int) env_value('MQTT_PORT', '1883')
        );

        $client->subscribe($topics, static function (string $topic, string $message) use ($handler): void {
            $payload = json_decode($message, true);

            if (!is_array($payload)) {
                echo sprintf("[%s] Payload JSON tidak valid dari topic %s\n", date('Y-m-d H:i:s'), $topic);
                return;
            }

            try {
                $result = $handler->handle($payload);
                echo sprintf(
                    "[%s] OK %s %s: %s\n",
                    date('Y-m-d H:i:s'),
                    $topic,
                    $result['type'],
                    $result['message']
                );
            } catch (Throwable $throwable) {
                echo sprintf(
                    "[%s] ERROR %s: %s\n",
                    date('Y-m-d H:i:s'),
                    $topic,
                    $throwable->getMessage()
                );
            }
        });
    } catch (Throwable $throwable) {
        echo sprintf(
            "[%s] MQTT worker reconnect dalam %d detik: %s\n",
            date('Y-m-d H:i:s'),
            $reconnectDelay,
            $throwable->getMessage()
        );
        sleep($reconnectDelay);
    }
}
