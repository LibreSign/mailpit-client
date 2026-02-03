<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Message;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use LibreSign\Mailpit\Message\Contact;

class ContactTest extends TestCase
{
    #[Test]
    #[DataProvider('contactProvider')]
    public function it_should_parse_to_email_address_and_name(string $input, Contact $expectedContact): void
    {
        $this->assertEquals($expectedContact, Contact::fromString($input));
    }

    /**
     * @return array<string, array{string, Contact}>
     */
    public static function contactProvider(): array
    {
        return [
            'e-mail address only' => [
                'me@myself.example',
                new Contact('me@myself.example')
            ],
            'e-mail address and name' => [
                'Me <me@myself.example>',
                new Contact('me@myself.example', 'Me')
            ],
            'e-mail address and name with <' => [
                'I <3 email <me@myself.example>',
                new Contact('me@myself.example', 'I <3 email')
            ],
            'e-mail address and name with >' => [
                'I > email <me@myself.example>',
                new Contact('me@myself.example', 'I > email')
            ],
            'e-mail address and name with double quotes' => [
                'Me \"Email King\" Myself <me@myself.example>',
                new Contact('me@myself.example', 'Me "Email King" Myself')
            ],
        ];
    }

    #[Test]
    public function it_should_indicate_when_equal_to_other_contact_based_on_email_address_only(): void
    {
        $this->assertTrue((new Contact('me@myself.example'))->equals(new Contact('me@myself.example')));
    }

    #[Test]
    public function it_should_indicate_when_equal_to_other_contact_when_either_contact_has_no_name(): void
    {
        $this->assertTrue((new Contact('me@myself.example', 'Me'))->equals(new Contact('me@myself.example')));
        $this->assertTrue((new Contact('me@myself.example'))->equals(new Contact('me@myself.example', 'Me')));
    }

    #[Test]
    public function it_should_indicate_when_not_equal_to_each_other_when_names_are_not_equal(): void
    {
        $this->assertFalse((new Contact('me@myself.example', 'Me'))->equals(new Contact('me@myself.example', 'Myself')));
    }

    #[Test]
    public function it_should_indicate_when_not_equal_to_each_other_when_email_addresses_are_not_equal(): void
    {
        $this->assertFalse((new Contact('me@myself.example', 'Me'))->equals(new Contact('someoneelse@myself.example', 'Me')));
        $this->assertFalse((new Contact('me@myself.example', 'Me'))->equals(new Contact('someoneelse@myself.example')));
        $this->assertFalse((new Contact('me@myself.example'))->equals(new Contact('someoneelse@myself.example')));
    }
}
