<?php

declare(strict_types=1);

final class MqttClient
{
    /** @var resource|null */
    private $socket = null;
    private int $packetId = 1;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $clientId,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly int $keepAlive = 60,
    ) {
    }

    public function connect(): void
    {
        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $this->host, $this->port),
            $errorCode,
            $errorMessage,
            10
        );

        if ($socket === false) {
            throw new RuntimeException(sprintf('Gagal konek MQTT broker: %s (%d)', $errorMessage, $errorCode));
        }

        stream_set_timeout($socket, 1);
        $this->socket = $socket;

        $flags = 0x02;
        $payload = $this->encodeString($this->clientId);

        if ($this->username !== null && $this->username !== '') {
            $flags |= 0x80;
            $payload .= $this->encodeString($this->username);
        }

        if ($this->password !== null && $this->password !== '') {
            $flags |= 0x40;
            $payload .= $this->encodeString($this->password);
        }

        $variableHeader = $this->encodeString('MQTT') . chr(4) . chr($flags) . pack('n', $this->keepAlive);
        $this->writePacket(0x10, $variableHeader . $payload);

        $packet = $this->readPacket();
        if ($packet === null || $packet['type'] !== 2 || strlen($packet['body']) < 2 || ord($packet['body'][1]) !== 0) {
            throw new RuntimeException('MQTT broker menolak koneksi.');
        }
    }

    /**
     * @param array<string, int> $topics
     */
    public function subscribe(array $topics, callable $onMessage): void
    {
        if ($this->socket === null) {
            $this->connect();
        }

        $packetId = $this->nextPacketId();
        $payload = '';

        foreach ($topics as $topic => $qos) {
            $payload .= $this->encodeString($topic) . chr($qos);
        }

        $this->writePacket(0x82, pack('n', $packetId) . $payload);
        $packet = $this->readPacket();

        if ($packet === null || $packet['type'] !== 9) {
            throw new RuntimeException('Tidak menerima SUBACK dari MQTT broker.');
        }

        $lastPing = time();

        while (true) {
            $packet = $this->readPacket();

            if ($packet === null) {
                if (time() - $lastPing >= max(10, $this->keepAlive - 5)) {
                    $this->writePacket(0xC0, '');
                    $lastPing = time();
                }

                continue;
            }

            if ($packet['type'] !== 3) {
                continue;
            }

            $publish = $this->parsePublish($packet['flags'], $packet['body']);
            $onMessage($publish['topic'], $publish['payload']);

            if ($publish['packet_id'] !== null) {
                $this->writePacket(0x40, pack('n', $publish['packet_id']));
            }
        }
    }

    private function parsePublish(int $flags, string $body): array
    {
        $topicLength = unpack('n', substr($body, 0, 2))[1];
        $topic = substr($body, 2, $topicLength);
        $offset = 2 + $topicLength;
        $packetId = null;
        $qos = ($flags & 0x06) >> 1;

        if ($qos > 0) {
            $packetId = unpack('n', substr($body, $offset, 2))[1];
            $offset += 2;
        }

        return [
            'topic' => $topic,
            'payload' => substr($body, $offset),
            'packet_id' => $packetId,
        ];
    }

    private function writePacket(int $header, string $body): void
    {
        if ($this->socket === null) {
            throw new RuntimeException('Socket MQTT belum terhubung.');
        }

        fwrite($this->socket, chr($header) . $this->encodeRemainingLength(strlen($body)) . $body);
    }

    private function readPacket(): ?array
    {
        if ($this->socket === null) {
            throw new RuntimeException('Socket MQTT belum terhubung.');
        }

        $firstByte = fread($this->socket, 1);
        if ($firstByte === '' || $firstByte === false) {
            return null;
        }

        $remainingLength = 0;
        $multiplier = 1;

        do {
            $encodedByte = fread($this->socket, 1);
            if ($encodedByte === '' || $encodedByte === false) {
                return null;
            }

            $byte = ord($encodedByte);
            $remainingLength += ($byte & 127) * $multiplier;
            $multiplier *= 128;
        } while (($byte & 128) !== 0);

        $body = $this->readExact($remainingLength);

        return [
            'type' => ord($firstByte) >> 4,
            'flags' => ord($firstByte) & 0x0F,
            'body' => $body,
        ];
    }

    private function readExact(int $length): string
    {
        if ($this->socket === null) {
            throw new RuntimeException('Socket MQTT belum terhubung.');
        }

        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($this->socket, $length - strlen($buffer));

            if ($chunk === '' || $chunk === false) {
                throw new RuntimeException('Koneksi MQTT terputus saat membaca paket.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function encodeString(string $value): string
    {
        return pack('n', strlen($value)) . $value;
    }

    private function encodeRemainingLength(int $length): string
    {
        $encoded = '';

        do {
            $byte = $length % 128;
            $length = intdiv($length, 128);

            if ($length > 0) {
                $byte |= 128;
            }

            $encoded .= chr($byte);
        } while ($length > 0);

        return $encoded;
    }

    private function nextPacketId(): int
    {
        $packetId = $this->packetId++;

        if ($this->packetId > 65535) {
            $this->packetId = 1;
        }

        return $packetId;
    }
}
