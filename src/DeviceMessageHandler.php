<?php

declare(strict_types=1);

final class DeviceMessageHandler
{
    public function __construct(private readonly AlarmRepository $alarmRepository)
    {
    }

    public function handle(array $payload): array
    {
        $btnCode = trim((string) ($payload['code_button'] ?? ''));
        $ip = trim((string) ($payload['ip'] ?? ''));

        if ($btnCode === '' || strlen($btnCode) > 10) {
            throw new InvalidArgumentException('code_button wajib diisi dan maksimal 10 karakter.');
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('ip tidak valid.');
        }

        if (array_key_exists('button', $payload) || array_key_exists('status', $payload)) {
            $button = $this->normalizedButtonType($payload['button'] ?? null);
            $state = $this->normalizedAlarmState($payload['status'] ?? null);

            if ($button === null || $state === null) {
                throw new InvalidArgumentException('button harus Red/Blue dan status harus ON/OFF.');
            }

            return [
                'type' => 'state',
                'message' => 'State alarm berhasil diterima.',
                'data' => $this->alarmRepository->recordButtonState($btnCode, $button, $state, $ip),
            ];
        }

        $deviceType = trim((string) ($payload['device_type'] ?? ''));

        if (strtolower($deviceType) !== 'button') {
            throw new InvalidArgumentException('device_type harus Button untuk payload heartbeat.');
        }

        return [
            'type' => 'heartbeat',
            'message' => 'Ping device berhasil diterima.',
            'data' => [
                'device' => $this->alarmRepository->recordDevicePing($btnCode, $ip),
            ],
        ];
    }

    private function normalizedButtonType(mixed $button): ?string
    {
        return match (strtolower(trim((string) $button))) {
            'red' => 'Red',
            'blue' => 'Blue',
            default => null,
        };
    }

    private function normalizedAlarmState(mixed $state): ?string
    {
        return match (strtoupper(trim((string) $state))) {
            'ON' => 'ON',
            'OFF' => 'OFF',
            default => null,
        };
    }
}
