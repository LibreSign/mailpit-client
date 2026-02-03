<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests;

use RuntimeException;

final class MailpitConfig
{
    /**
     * @var string
     */
    private static $host;

    /**
     * @var int
     */
    private static $port;

    public static function getHost(): string
    {
        self::parse();

        return self::$host;
    }

    public static function getPort(): int
    {
        self::parse();

        return self::$port;
    }

    private static function parse(): void
    {
        if (isset(self::$host)) {
            return;
        }

        $dsn = $_ENV['mailpit_smtp_dsn'] ?? 'smtp://localhost:1025';

        $info = parse_url($dsn);

        if (!is_array($info)) {
            throw new RuntimeException(sprintf('Unable to parse DSN "%s"', $dsn));
        }

        if (!isset($info['host'])) {
            throw new RuntimeException(
                sprintf(
                    'Unable to parse host from Mailpit DSN "%s"',
                    $dsn
                )
            );
        }

        if (!isset($info['port'])) {
            throw new RuntimeException(
                sprintf(
                    'Unable to parse port from Mailpit DSN "%s"',
                    $dsn
                )
            );
        }

        static::$host = $info['host'];
        static::$port = $info['port'];
    }
}
