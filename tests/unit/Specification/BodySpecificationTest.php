<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use LibreSign\Mailpit\Specification\BodySpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BodySpecificationTest extends TestCase
{
    #[Test]
    public function it_should_be_satisfied_when_snippet_is_found_in_body(): void
    {
        $specification = new BodySpecification('Hi');
        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    #[Test]
    public function it_should_not_be_satisfied_when_snippet_is_not_found_in_body(): void
    {
        $specification = new BodySpecification('Hello');
        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
