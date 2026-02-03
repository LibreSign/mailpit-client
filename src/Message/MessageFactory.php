<?php
declare(strict_types=1);

namespace LibreSign\Mailpit\Message;

class MessageFactory
{
    /**
     * @param array<string, mixed> $mailpitResponse
     */
    public static function fromMailpitResponse(array $mailpitResponse): Message
    {
        $headers = Headers::fromMailpitResponse($mailpitResponse);

        $fromContact = self::convertContact(
            self::normalizeStringKeyedArray($mailpitResponse['From'] ?? null),
            $headers->get('From', '')
        );

        $toContacts = self::convertContactCollection(
            self::normalizeArrayOfStringKeyedArrays($mailpitResponse['To'] ?? null),
            $headers->get('To', '')
        );
        $ccContacts = self::convertContactCollection(
            self::normalizeArrayOfStringKeyedArrays($mailpitResponse['Cc'] ?? null),
            $headers->get('Cc', '')
        );
        $bccContacts = self::convertContactCollection(
            self::normalizeArrayOfStringKeyedArrays($mailpitResponse['Bcc'] ?? null),
            $headers->get('Bcc', '')
        );

        $body = self::getString($mailpitResponse, 'HTML');
        if ($body === '') {
            $body = self::getString($mailpitResponse, 'Text');
        }

        $body = self::decodeBody($headers, $body);
        $body = preg_replace('/\r\n$/', '', $body) ?? '';

        $attachments = self::buildAttachments(
            self::normalizeArrayOfStringKeyedArrays($mailpitResponse['Attachments'] ?? null),
            self::normalizeStringMap($mailpitResponse['AttachmentsData'] ?? null)
        );

        return new Message(
            self::getString($mailpitResponse, 'ID'),
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
            $address = $contactData['Address'] ?? null;
            if (is_string($address) && $address !== '') {
                $name = $contactData['Name'] ?? null;
                $name = is_string($name) ? $name : null;

                return new Contact($address, $name);
            }
        }

        if ($headerFallback !== '') {
            return Contact::fromString($headerFallback);
        }

        return new Contact('');
    }

    /**
     * @param array<int, array<string, mixed>> $contactsData
     */
    private static function convertContactCollection(
        array $contactsData,
        string $headerFallback,
    ): ContactCollection {
        if ($headerFallback !== '') {
            return ContactCollection::fromString($headerFallback);
        }

        $contacts = [];
        foreach ($contactsData as $contactData) {
            $address = $contactData['Address'] ?? null;
            if (!is_string($address) || $address === '') {
                continue;
            }

            $name = $contactData['Name'] ?? null;
            $contacts[] = new Contact($address, is_string($name) ? $name : null);
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
            $partId = $attachment['PartID'] ?? null;
            if (!is_string($partId) && !is_int($partId)) {
                continue;
            }

            $partId = (string) $partId;

            $fileName = $attachment['FileName'] ?? '';
            if (!is_string($fileName)) {
                $fileName = '';
            }

            $contentType = $attachment['ContentType'] ?? 'application/octet-stream';
            if (!is_string($contentType)) {
                $contentType = 'application/octet-stream';
            }

            $result[] = new Mime\Attachment(
                $fileName,
                $contentType,
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

    /**
     * @param array<mixed, mixed> $data
     */
    private static function getString(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private static function normalizeStringKeyedArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeArrayOfStringKeyedArrays(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $normalizedItem = self::normalizeStringKeyedArray($item);
            if ($normalizedItem !== []) {
                $result[] = $normalizedItem;
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    private static function normalizeStringMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $mapKey => $mapValue) {
            if (is_string($mapValue)) {
                $result[(string) $mapKey] = $mapValue;
            }
        }

        return $result;
    }
}
