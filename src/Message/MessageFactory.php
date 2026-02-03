<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message;

use function quoted_printable_decode;

class MessageFactory
{
    /**
     * @param mixed[] $mailpitResponse
     */
    public static function fromMailpitResponse(array $mailpitResponse): Message
    {
        $headers = Headers::fromMailpitResponse($mailpitResponse);

        $fromContact = self::convertContact($mailpitResponse['From'] ?? null, $headers->get('From', ''));
        $toContacts = self::convertContactCollection($mailpitResponse['To'] ?? [], $headers->get('To', ''));
        $ccContacts = self::convertContactCollection($mailpitResponse['Cc'] ?? [], $headers->get('Cc', ''));
        $bccContacts = self::convertContactCollection($mailpitResponse['Bcc'] ?? [], $headers->get('Bcc', ''));

        $body = $mailpitResponse['HTML'] ?? '';
        if ($body === '' || $body === null) {
            $body = $mailpitResponse['Text'] ?? '';
        }

        $body = static::decodeBody($headers, $body ?? '');
        $body = preg_replace('/\r\n$/', '', $body);

        $attachments = self::buildAttachments(
            $mailpitResponse['Attachments'] ?? [],
            $mailpitResponse['AttachmentsData'] ?? []
        );

        return new Message(
            $mailpitResponse['ID'],
            $fromContact,
            $toContacts,
            $ccContacts,
            $bccContacts,
            $headers->get('Subject', ''),
            $body,
            $attachments,
            $headers
        );
    }

    /**
     * @param mixed[]|null $contactData
     */
    private static function convertContact(array | null $contactData, string $headerFallback): Contact
    {
        if (is_array($contactData)) {
            if (isset($contactData['Address'])) {
                return new Contact($contactData['Address'], $contactData['Name'] ?? null);
            }
        }

        if ($headerFallback !== '') {
            return Contact::fromString($headerFallback);
        }

        return new Contact('');
    }

    /**
     * @param mixed[]|null $contactsData
     */
    private static function convertContactCollection(
        ?array $contactsData,
        string $headerFallback,
    ): ContactCollection {
        if ($headerFallback !== '') {
            return ContactCollection::fromString($headerFallback);
        }

        $contacts = [];
        foreach ($contactsData ?? [] as $contactData) {
            if (!is_array($contactData)) {
                continue;
            }

            if (isset($contactData['Address'])) {
                $contacts[] = new Contact($contactData['Address'], $contactData['Name'] ?? null);
                continue;
            }
        }

        return new ContactCollection($contacts);
    }

    /**
     * @param array<int, array<string, mixed>> $attachments
     * @param array<string, string> $attachmentContents
     * @return array<int, Mime\Attachment>
     */
    private static function buildAttachments(array $attachments, array $attachmentContents): array
    {
        $result = [];

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $partId = $attachment['PartID'] ?? null;
            if (!is_string($partId)) {
                continue;
            }

            $result[] = new Mime\Attachment(
                $attachment['FileName'] ?? '',
                $attachment['ContentType'] ?? 'application/octet-stream',
                $attachmentContents[$partId] ?? ''
            );
        }

        return $result;
    }

    private static function decodeBody(Headers $headers, string $body): string
    {
        if ($headers->get('Content-Transfer-Encoding') === 'quoted-printable') {
            return quoted_printable_decode($body);
        }

        return $body;
    }
}
