<?php
declare(strict_types=1);

namespace LibreSign\Mailpit;

use Generator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;
use LibreSign\Mailpit\Message\Message;
use LibreSign\Mailpit\Message\MessageFactory;
use LibreSign\Mailpit\Specification\Specification;
use RuntimeException;

class MailpitClient
{
    private ResponseNormalizer $normalizer;

    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUri
    ) {
        $this->baseUri = rtrim($baseUri, '/');
        $this->normalizer = new ResponseNormalizer();
    }

    /**
     * @return Generator|Message[]
     */
    public function findAllMessages(int $limit = 50): Generator
    {
        $start = 0;
        while (true) {
            $allMessageData = $this->fetchMessageList($limit, $start);
            $messages = $this->extractMessagesFromResponse($allMessageData);

            foreach ($messages as $messageId) {
                yield $this->getMessageById($messageId);
            }

            $start += $limit;
            $total = $this->extractTotalFromResponse($allMessageData);

            if ($start >= $total) {
                return;
            }
        }
    }

    /**
     * @return Message[]
     */
    public function findLatestMessages(int $numberOfMessages): array
    {
        $allMessageData = $this->fetchMessageList($numberOfMessages, 0);
        $messageIds = $this->extractMessagesFromResponse($allMessageData);

        $messages = [];
        foreach ($messageIds as $messageId) {
            $messages[] = $this->getMessageById($messageId);
        }

        return $messages;
    }

    /**
     * @return Message[]
     */
    public function findMessagesSatisfying(Specification $specification): array
    {
        return array_filter(
            iterator_to_array($this->findAllMessages()),
            static function (Message $message) use ($specification) {
                return $specification->isSatisfiedBy($message);
            }
        );
    }

    public function getLastMessage(): Message
    {
        $messages = $this->findLatestMessages(1);

        if (count($messages) === 0) {
            throw new NoSuchMessageException('No last message found. Inbox empty?');
        }

        return $messages[0];
    }

    public function getNumberOfMessages(): int
    {
        $data = $this->fetchMessageList(1, 0);
        return $this->extractTotalFromResponse($data);
    }

    public function deleteMessage(string $messageId): void
    {
        $body = $this->encodeJson(['IDs' => [$messageId]], sprintf('delete message %s', $messageId));

        $request = $this->requestFactory->createRequest('DELETE', sprintf('%s/api/v1/messages', $this->baseUri))
            ->withBody($this->streamFactory->createStream($body))
            ->withHeader('Content-Type', 'application/json');

        $this->httpClient->sendRequest($request);
    }

    public function purgeMessages(): void
    {
        $request = $this->requestFactory->createRequest('DELETE', sprintf('%s/api/v1/messages', $this->baseUri));

        $this->httpClient->sendRequest($request);
    }

    public function releaseMessage(string $messageId, string $emailAddress): void
    {
        $body = $this->encodeJson(
            ['To' => [$emailAddress]],
            sprintf('release message %s', $messageId)
        );

        $request = $this->requestFactory->createRequest(
            'POST',
            sprintf('%s/api/v1/message/%s/release', $this->baseUri, $messageId)
        )
            ->withBody($this->streamFactory->createStream($body))
            ->withHeader('Content-Type', 'application/json');

        $this->httpClient->sendRequest($request);
    }

    public function getMessageById(string $messageId): Message
    {
        $messageData = $this->fetchMessageData($messageId);
        $messageData = $this->enrichMessageData($messageId, $messageData);

        return MessageFactory::fromMailpitResponse($messageData);
    }


    /**
     * @return array<string, array<int, string>>
     */
    private function fetchMessageHeaders(string $messageId): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/api/v1/message/%s/headers', $this->baseUri, $messageId)
        );

        $response = $this->httpClient->sendRequest($request);

        $headers = json_decode($response->getBody()->getContents(), true);

        return $this->normalizer->normalizeHeaderMap($headers);
    }

    private function readStreamContents(StreamInterface $stream): string
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $contents = $stream->getContents();
        if ($contents === '' && $stream->isSeekable()) {
            $stream->rewind();
            $contents = $stream->getContents();
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchMessageList(int $limit, int $start): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf(
                '%s/api/v1/messages?limit=%d&start=%d',
                $this->baseUri,
                $limit,
                $start
            )
        );

        $response = $this->httpClient->sendRequest($request);

        return $this->normalizer->decodeJsonResponse(
            $response->getBody()->getContents(),
            'list messages'
        );
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array<int, string>
     */
    private function extractMessagesFromResponse(array $responseData): array
    {
        $messages = $responseData['messages'] ?? null;
        if (!is_array($messages)) {
            throw new RuntimeException('Invalid Mailpit response: messages list missing or not an array.');
        }

        $messageIds = [];
        foreach ($messages as $messageData) {
            $messageId = $this->extractMessageId($messageData);
            if ($messageId !== null) {
                $messageIds[] = $messageId;
            }
        }

        return $messageIds;
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function extractTotalFromResponse(array $responseData): int
    {
        $total = $responseData['total'] ?? 0;
        if (is_int($total)) {
            return $total;
        }

        return is_numeric($total) ? (int) $total : 0;
    }

    /**
     * @param array<mixed, mixed> $data
     */
    private function encodeJson(array $data, string $context): string
    {
        $body = json_encode($data);

        if (false === $body) {
            throw new RuntimeException(sprintf('Unable to JSON encode data to %s', $context));
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchMessageData(string $messageId): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/api/v1/message/%s', $this->baseUri, $messageId)
        );

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 404) {
            throw NoSuchMessageException::forMessageId($messageId);
        }

        $body = $response->getBody()->getContents();
        if (trim($body) === '') {
            throw NoSuchMessageException::forMessageId($messageId);
        }

        return $this->normalizer->decodeJsonResponse($body, 'message details');
    }

    /**
     * @param array<int, array<string, mixed>> $attachments
     * @return array<string, string>
     */
    private function fetchAttachmentsData(string $messageId, array $attachments): array
    {
        $contents = [];

        foreach ($attachments as $attachment) {
            $partId = $this->extractPartId($attachment);
            if ($partId === null) {
                continue;
            }

            $contents[$partId] = $this->fetchAttachmentContent($messageId, $partId);
        }

        return $contents;
    }

    /**
     * @param array<string, mixed> $messageData
     * @return array<string, mixed>
     */
    private function enrichMessageData(string $messageId, array $messageData): array
    {
        $messageData['Headers'] = $this->fetchMessageHeaders($messageId);

        $attachments = $this->normalizer->normalizeArrayOfStringKeyedArrays($messageData['Attachments'] ?? []);
        $messageData['Attachments'] = $attachments;
        $messageData['AttachmentsData'] = $this->fetchAttachmentsData($messageId, $attachments);

        return $messageData;
    }

    /**
     * @param array<string, mixed> $attachment
     */
    private function extractPartId(array $attachment): ?string
    {
        $partId = $attachment['PartID'] ?? null;
        if (!is_string($partId) && !is_int($partId)) {
            return null;
        }

        return (string) $partId;
    }

    private function fetchAttachmentContent(string $messageId, string $partId): string
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf('%s/api/v1/message/%s/part/%s', $this->baseUri, $messageId, $partId)
        );

        $response = $this->httpClient->sendRequest($request);
        return $this->readStreamContents($response->getBody());
    }

    /**
     * @param mixed $messageData
     */
    private function extractMessageId(mixed $messageData): ?string
    {
        if (!is_array($messageData)) {
            return null;
        }

        $messageId = $messageData['ID'] ?? null;
        if (!is_string($messageId) || $messageId === '') {
            return null;
        }

        return $messageId;
    }
}
