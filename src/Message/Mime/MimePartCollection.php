<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message\Mime;

class MimePartCollection
{
    /**
     * @param MimePart[] $mimeParts
     */
    private function __construct(private array $mimeParts)
    {
    }

    /**
     * @param mixed[] $mimeParts
     */
    public static function fromMailpitResponse(array $mimeParts): self
    {
        return new self(self::flattenParts($mimeParts));
    }

    /**
     * @param mixed[] $mimeParts
     *
     * @return array<int, MimePart>
     */
    protected static function flattenParts(array $mimeParts): array
    {
        $flattenedParts = [];
        foreach ($mimeParts as $mimePart) {
            if (!is_array($mimePart)) {
                continue;
            }

            $mimeData = $mimePart['MIME'] ?? null;
            if (!is_array($mimeData) || !isset($mimeData['Parts']) || !is_array($mimeData['Parts'])) {
                $flattenedParts[] = MimePart::fromMailpitResponse($mimePart);
                continue;
            }

            $flattenedParts = array_merge($flattenedParts, self::flattenParts($mimeData['Parts']));
        }

        return $flattenedParts;
    }

    public function isEmpty(): bool
    {
        return count($this->mimeParts) === 0;
    }

    /**
     * @return Attachment[]
     */
    public function getAttachments(): array
    {
        $attachments = [];
        foreach ($this->mimeParts as $mimePart) {
            if (!$mimePart->isAttachment()) {
                continue;
            }

            $attachments[] = new Attachment(
                $mimePart->getFilename(),
                $mimePart->getContentType(),
                $mimePart->getBody()
            );
        }

        return $attachments;
    }

    public function getBody(): string
    {
        foreach ($this->mimeParts as $mimePart) {
            if ($mimePart->isAttachment()) {
                continue;
            }

            if (stripos($mimePart->getContentType(), 'text/html') === 0) {
                return $mimePart->getBody();
            }
        }

        foreach ($this->mimeParts as $mimePart) {
            if ($mimePart->isAttachment()) {
                continue;
            }

            if (stripos($mimePart->getContentType(), 'text/plain') === 0) {
                return $mimePart->getBody();
            }
        }

        return '';
    }
}
