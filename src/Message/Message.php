<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message;

use InvalidArgumentException;
use LibreSign\Mailpit\Message\Mime\Attachment;

use function sprintf;

class Message
{
    /**
     * @param array<int, Attachment> $attachments
     */
    public function __construct(
        public string $messageId,
        public Contact $sender,
        public ContactCollection $recipients,
        public ContactCollection $ccRecipients,
        public ContactCollection $bccRecipients,
        public string $subject,
        public string $body,
        public array $attachments,
        public Headers $headers
    ) {
        foreach ($attachments as $i => $attachment) {
            /** @phpstan-ignore instanceof.alwaysTrue */
            if (!$attachment instanceof Attachment) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Element %d of attachments array passed to "%s" was not an instance of "%s"',
                        $i,
                        self::class,
                        Attachment::class
                    )
                );
            }
        }
    }
}
