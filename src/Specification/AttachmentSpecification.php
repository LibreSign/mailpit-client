<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;
use Override;

final class AttachmentSpecification implements Specification
{
    public function __construct(private string $filename)
    {
    }

    #[Override]
    public function isSatisfiedBy(Message $message): bool
    {
        foreach ($message->attachments as $attachment) {
            if ($attachment->filename === $this->filename) {
                return true;
            }
        }

        return false;
    }
}
