<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Tests\unit\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use LibreSign\Mailpit\Specification\AndSpecification;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\AlwaysSatisfied;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\MessageFactory;
use LibreSign\Mailpit\Tests\unit\Specification\Fixtures\NeverSatisfied;

class AndSpecificationTest extends TestCase
{
    #[Test]
    public function it_should_be_satisfied_when_both_specification_are_satisfied(): void
    {
        $andSpecification = new AndSpecification(new AlwaysSatisfied(), new AlwaysSatisfied());

        $this->assertTrue($andSpecification->isSatisfiedBy(MessageFactory::dummy()));
    }

    #[Test]
    #[DataProvider('nonSatisfiedAndSpecificationsProvider')]
    public function it_should_not_be_satisfied_when_either_specification_is_not_satisfied(AndSpecification $specification): void
    {
        $this->assertFalse($specification->isSatisfiedBy(MessageFactory::dummy()));
    }

    /**
     * @return array<string, array{AndSpecification}>
     */
    public static function nonSatisfiedAndSpecificationsProvider(): array
    {
        return [
            'left not satisfied' => [new AndSpecification(new NeverSatisfied(), new AlwaysSatisfied())],
            'right not satisfied' => [new AndSpecification(new AlwaysSatisfied(), new NeverSatisfied())],
            'left and right not satisfied' => [new AndSpecification(new NeverSatisfied(), new NeverSatisfied())],
        ];
    }

    #[Test]
    public function it_should_return_specification_when_building_compound_from_one_specification(): void
    {
        $this->assertEquals(new AlwaysSatisfied(), AndSpecification::all(new AlwaysSatisfied()));
    }

    #[Test]
    public function it_should_build_compound_and_specifications_from_multiple_specifications(): void
    {
        $expected = new AndSpecification(
            new AlwaysSatisfied(),
            new AndSpecification(
                new AlwaysSatisfied(),
                new AlwaysSatisfied()
            )
        );

        $this->assertEquals($expected, AndSpecification::all(new AlwaysSatisfied(), new AlwaysSatisfied(), new AlwaysSatisfied()));
    }
}
