<?php

declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit;

use Http\Client\Curl\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use LibreSign\Mailpit\MailpitClient;

final class MailpitClientTest extends TestCase
{
    #[Test]
    public function it_should_remove_trailing_slashes_from_base_uri(): void
    {
        $client = new MailpitClient(new Client(), new Psr17Factory(), new Psr17Factory(), 'http://mailpit/');

        $property = new ReflectionProperty($client, 'baseUri');
        $property->setAccessible(true);

        $this->assertEquals('http://mailpit', $property->getValue($client));
    }
}
