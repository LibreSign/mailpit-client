<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Contact;
use LibreSign\Mailpit\Message\Message;

final class RecipientSpecification implements Specification
{
    public function __construct(private Contact $recipient)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return $message->recipients->contains($this->recipient);
    }
}
