<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;

interface Specification
{
    public function isSatisfiedBy(Message $message): bool;
}
