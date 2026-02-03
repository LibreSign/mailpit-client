<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use LibreSign\Mailpit\Message\Contact;
use LibreSign\Mailpit\Specification\RecipientSpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;

class RecipientSpecificationTest extends TestCase
{
    #[Test]
    public function it_should_be_satisfied_on_same_recipient_email_address(): void
    {
        $specification = new RecipientSpecification(new Contact('someoneelse@myself.example'));

        $this->assertTrue($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    #[Test]
    public function it_should_not_be_satisfied_on_different_recipient_email_address_and_name(): void
    {
        $specification = new RecipientSpecification(new Contact('notme@myself.example'));

        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }
}
