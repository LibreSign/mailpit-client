<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Specification;

use LibreSign\Mailpit\Message\Message;
use Override;

use function array_slice;
use function count;

final class AndSpecification implements Specification
{
    public function __construct(private Specification $left, private Specification $right)
    {
    }

    public static function all(Specification $specification, Specification ...$other): Specification
    {
        if (count($other) === 0) {
            return $specification;
        }

        if (count($other) === 1) {
            return new self($specification, $other[0]);
        }

        return new self($specification, self::all($other[0], ...array_slice($other, 1)));
    }

    #[Override]
    public function isSatisfiedBy(Message $message): bool
    {
        return $this->left->isSatisfiedBy($message) && $this->right->isSatisfiedBy($message);
    }
}
