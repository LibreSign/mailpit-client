<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use LibreSign\Mailpit\Message\Contact;
use LibreSign\Mailpit\Specification\SenderSpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;

final class SenderSpecificationTest extends TestCase
{
    #[Test]
    public function it_should_be_satisfied_on_same_sender_email_address(): void
    {
        $specification = new SenderSpecification(new Contact('me@myself.example'));

        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    #[Test]
    public function it_should_not_be_satisfied_on_different_sender_email_address_and_name(): void
    {
        $specification = new SenderSpecification(new Contact('someonelese@myself.example'));

        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
