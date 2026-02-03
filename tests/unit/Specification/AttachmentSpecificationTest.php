<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use LibreSign\Mailpit\Specification\AttachmentSpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AttachmentSpecificationTest extends TestCase
{
    #[Test]
    public function it_should_be_satisfied_when_message_has_attachment(): void
    {
        $specification = new AttachmentSpecification('lorem-ipsum.txt');
        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    #[Test]
    public function it_should_not_be_satisfied_when_messages_does_not_have_attachment(): void
    {
        $specification = new AttachmentSpecification('lorem-ipsum.jpg');
        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
