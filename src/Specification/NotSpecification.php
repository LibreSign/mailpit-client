<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;
use Override;

final class NotSpecification implements Specification
{
    public function __construct(private Specification $wrapped)
    {
    }

    #[Override]
    public function isSatisfiedBy(Message $message): bool
    {
        return !$this->wrapped->isSatisfiedBy($message);
    }
}
