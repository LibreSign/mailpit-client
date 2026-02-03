<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification\Fixtures;

use LibreSign\Mailpit\Message\Message;
use LibreSign\Mailpit\Specification\Specification;

final class NeverSatisfied implements Specification
{
    public function isSatisfiedBy(Message $message): bool
    {
        return false;
    }
}
