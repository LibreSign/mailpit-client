<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use LibreSign\Mailpit\Specification\SubjectSpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;

class SubjectSpecificationTest extends TestCase
{
    #[Test]
    public function it_should_be_satisfied_by_message_with_specified_subject(): void
    {
        $this->assertTrue((new SubjectSpecification('Hello world!'))->isSatisfiedBy(MessageFactory::dummy()));
    }

    #[Test]
    public function it_should_not_be_satisfied_by_message_with_different_subject(): void
    {
        $this->assertFalse((new SubjectSpecification('Hi world!'))->isSatisfiedBy(MessageFactory::dummy()));
    }
}
