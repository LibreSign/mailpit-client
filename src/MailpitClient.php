<?php
declare(strict_types=1);

namespace LibreSign\Mailpit;

use Generator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use LibreSign\Mailpit\Message\Message;
use LibreSign\Mailpit\Message\MessageFactory;
use LibreSign\Mailpit\Specification\Specification;
use RuntimeException;

use function array_filter;
use function assert;
use function count;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function rtrim;
use function sprintf;

class MailpitClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUri
    ) {
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @return Generator|Message[]
     */
    public function findAllMessages(int $limit = 50): Generator
    {
        $start = 0;
        while (true) {
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

            $allMessageData = json_decode($response->getBody()->getContents(), true);

            foreach ($allMessageData['messages'] as $messageData) {
                if (!isset($messageData['ID'])) {
                    continue;
                }

                yield $this->getMessageById($messageData['ID']);
            }

            $start += $limit;

            if ($start >= $allMessageData['total']) {
                return;
            }
        }
    }

    /**
     * @return Message[]
     */
    public function findLatestMessages(int $numberOfMessages): array
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf(
                '%s/api/v1/messages?limit=%d',
                $this->baseUri,
                $numberOfMessages
            )
        );

        $response = $this->httpClient->sendRequest($request);

        $allMessageData = json_decode($response->getBody()->getContents(), true);

        $messages = [];
        foreach ($allMessageData['messages'] as $messageData) {
            if (!isset($messageData['ID'])) {
                continue;
            }

            $messages[] = $this->getMessageById($messageData['ID']);
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
        $request = $this->requestFactory->createRequest('GET', sprintf('%s/api/v1/messages?limit=1', $this->baseUri));

        $response = $this->httpClient->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true)['total'] ?? 0;
    }

    public function deleteMessage(string $messageId): void
    {
        $body = json_encode(['IDs' => [$messageId]]);

        if (false === $body) {
            throw new RuntimeException(
                sprintf('Unable to JSON encode data to delete message %s', $messageId)
            );
        }

        $request = $this->requestFactory->createRequest('DELETE', sprintf('%s/api/v1/messages', $this->baseUri))
            ->withBody($this->streamFactory->createStream($body))
            ->withHeader('Content-Type', 'application/json');

        /** @var RequestInterface $request */
        assert($request instanceof RequestInterface);

        $this->httpClient->sendRequest($request);
    }

    public function purgeMessages(): void
    {
        $request = $this->requestFactory->createRequest('DELETE', sprintf('%s/api/v1/messages', $this->baseUri));

        $this->httpClient->sendRequest($request);
    }

    public function releaseMessage(string $messageId, string $emailAddress): void
    {
        $body = json_encode([
            'To' => [$emailAddress],
        ]);

        if (false === $body) {
            throw new RuntimeException(
                sprintf('Unable to JSON encode data to release message %s', $messageId)
            );
        }

        $request = $this->requestFactory->createRequest(
            'POST',
            sprintf('%s/api/v1/message/%s/release', $this->baseUri, $messageId)
        )
            ->withBody($this->streamFactory->createStream($body))
            ->withHeader('Content-Type', 'application/json');

        /** @var RequestInterface $request */
        assert($request instanceof RequestInterface);

        $this->httpClient->sendRequest($request);
    }

    public function getMessageById(string $messageId): Message
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf(
                '%s/api/v1/message/%s',
                $this->baseUri,
                $messageId
            )
        );

        $response = $this->httpClient->sendRequest($request);

        $messageData = json_decode($response->getBody()->getContents(), true);

        if (null === $messageData) {
            throw NoSuchMessageException::forMessageId($messageId);
        }

        $messageData['Headers'] = $this->fetchMessageHeaders($messageId);
        $messageData['AttachmentsData'] = $this->fetchAttachmentsData($messageId, $messageData['Attachments'] ?? []);

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

        return is_array($headers) ? $headers : [];
    }

    /**
     * @param array<int, array<string, mixed>> $attachments
     * @return array<string, string>
     */
    private function fetchAttachmentsData(string $messageId, array $attachments): array
    {
        $contents = [];

        foreach ($attachments as $attachment) {
            if (!isset($attachment['PartID'])) {
                continue;
            }

            $request = $this->requestFactory->createRequest(
                'GET',
                sprintf('%s/api/v1/message/%s/part/%s', $this->baseUri, $messageId, $attachment['PartID'])
            );

            $response = $this->httpClient->sendRequest($request);
            $contents[$attachment['PartID']] = $response->getBody()->getContents();
        }

        return $contents;
    }
}
