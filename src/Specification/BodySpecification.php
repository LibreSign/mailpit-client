<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;
use Override;

use function str_contains;

final class BodySpecification implements Specification
{
    public function __construct(private string $snippet)
    {
    }

    #[Override]
    public function isSatisfiedBy(Message $message): bool
    {
        return str_contains($message->body, $this->snippet);
    }
}
