<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Contact;
use LibreSign\Mailpit\Message\Message;
use Override;

final class SenderSpecification implements Specification
{
    public function __construct(private Contact $sender)
    {
    }

    #[Override]
    public function isSatisfiedBy(Message $message): bool
    {
        return $message->sender->equals($this->sender);
    }
}
