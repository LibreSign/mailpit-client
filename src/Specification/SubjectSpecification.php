<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;
use Override;

final class SubjectSpecification implements Specification
{
    public function __construct(private string $subject)
    {
    }

    #[Override]
    public function isSatisfiedBy(Message $message): bool
    {
        return $message->subject === $this->subject;
    }
}
