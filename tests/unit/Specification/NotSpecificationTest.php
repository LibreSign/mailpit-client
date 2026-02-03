<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use LibreSign\Mailpit\Specification\NotSpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\AlwaysSatisfied;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\NeverSatisfied;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NotSpecificationTest extends TestCase
{
    #[Test]
    public function it_should_negate_answer_of_wrapped_specification(): void
    {
        $dummyMessage = MessageFactory::dummy();

        $this->assertFalse((new NotSpecification(new AlwaysSatisfied()))->isSatisfiedBy($dummyMessage));
        $this->assertTrue((new NotSpecification(new NeverSatisfied()))->isSatisfiedBy($dummyMessage));
    }
}
