<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification\Fixtures;

use LibreSign\Mailpit\Message\Contact;
use LibreSign\Mailpit\Message\ContactCollection;
use LibreSign\Mailpit\Message\Headers;
use LibreSign\Mailpit\Message\Message;
use LibreSign\Mailpit\Message\Mime\Attachment;

final class MessageFactory
{
    public static function dummy(): Message
    {
        return new Message(
            '1234',
            new Contact('me@myself.example', 'Myself'),
            new ContactCollection([new Contact('someoneelse@myself.example')]),
            new ContactCollection([]),
            new ContactCollection([]),
            'Hello world!',
            'Hi there',
            [
                new Attachment('lorem-ipsum.txt', 'text/plain', 'Lorem ipsum dolor sit amet!'),
            ],
            new Headers([])
        );
    }
}
