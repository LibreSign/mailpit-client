<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message\Mime;

class Attachment
{
    public function __construct(
        public string $filename,
        public string $mimeType,
        public string $content,
    ) {
    }
}
