<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message;

class Headers
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(private array $headers)
    {
    }

    /**
     * @param array<mixed, mixed> $mailpitResponse
     */
    public static function fromMailpitResponse(array $mailpitResponse): self
    {
        if (isset($mailpitResponse['Headers']) && is_array($mailpitResponse['Headers'])) {
            return self::fromRawHeaders($mailpitResponse['Headers']);
        }

        if (self::isHeadersArray($mailpitResponse)) {
            return self::fromRawHeaders($mailpitResponse);
        }

        return self::fromRawHeaders([]);
    }

    /**
     * @param array<mixed, mixed> $mimePart
     */
    public static function fromMimePart(array $mimePart): self
    {
        $headers = $mimePart['Headers'] ?? [];

        return self::fromRawHeaders(is_array($headers) ? $headers : []);
    }

    /**
     * @param array<mixed, mixed> $rawHeaders
     */
    private static function fromRawHeaders(array $rawHeaders): self
    {
        $headers = [];
        foreach ($rawHeaders as $name => $header) {
            if (!is_string($name)) {
                continue;
            }

            if (!is_array($header) || !isset($header[0]) || !is_string($header[0])) {
                continue;
            }

            $decoded = iconv_mime_decode($header[0]);

            $headers[strtolower($name)] = $decoded ?: $header[0];
        }

        return new Headers($headers);
    }


    /**
     * @param array<mixed, mixed> $data
     */
    private static function isHeadersArray(array $data): bool
    {
        foreach ($data as $value) {
            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }

    public function get(string $name, string $default = ''): string
    {
        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }

        return $default;
    }

    public function has(string $name): bool
    {
        $name = strtolower($name);

        return isset($this->headers[$name]);
    }
}
