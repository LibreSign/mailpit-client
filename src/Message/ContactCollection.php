<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Contact>
 */
class ContactCollection implements Countable, IteratorAggregate
{
    /**
     * @param Contact[] $contacts
     */
    public function __construct(private array $contacts)
    {
    }

    public static function fromString(string $contacts): ContactCollection
    {
        if (trim($contacts) === '') {
            return new self([]);
        }

        $rawContacts = str_getcsv($contacts, escape: '\\');

        $normalizedContacts = array_filter(
            array_map(
                static function ($contact): string {
                    return is_string($contact) ? trim($contact) : '';
                },
                $rawContacts
            ),
            static function (string $contact): bool {
                return $contact !== '';
            }
        );

        return new self(
            array_map(
                static function (string $contact) {
                    return Contact::fromString($contact);
                },
                $normalizedContacts
            )
        );
    }

    public function contains(Contact $needle): bool
    {
        foreach ($this->contacts as $contact) {
            if ($contact->equals($needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Traversable<Contact>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->contacts);
    }

    public function count(): int
    {
        return count($this->contacts);
    }
}
