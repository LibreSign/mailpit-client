<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;

final class NotSpecification implements Specification
{
    public function __construct(private Specification $wrapped)
    {
    }

    public function isSatisfiedBy(Message $message): bool
    {
        return !$this->wrapped->isSatisfiedBy($message);
    }
}
