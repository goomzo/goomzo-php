<?php

declare(strict_types=1);

namespace Goomzo\Tests;

use Goomzo\Exception\ApiException;
use Goomzo\Exception\ConnectionException;
use Goomzo\Goomzo;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ClientTest extends TestCase
{
    /** @var array<mixed>|\ArrayAccess<int, array<mixed>> */
    private array|\ArrayAccess $seen = [];

    /**
     * @param Response|ConnectException ...$answers
     */
    private function client(Response|ConnectException ...$answers): Goomzo
    {
        $this->seen = [];
        $stack = HandlerStack::create(new MockHandler(array_values($answers)));
        $stack->push(Middleware::history($this->seen));

        return new Goomzo(apiKey: 'gzk_test', baseUrl: 'https://api.test/', maxRetries: 2, httpClient: new Client(['handler' => $stack]));
    }

    /**
     * @param array<string, string> $headers
     */
    private static function json(int $status, mixed $body, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json', ...$headers], json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function request(int $index): RequestInterface
    {
        $entry = $this->seen[$index] ?? null;
        $request = is_array($entry) ? ($entry['request'] ?? null) : null;
        if (!$request instanceof RequestInterface) {
            self::fail("No request {$index} was made.");
        }

        return $request;
    }

    private function made(): int
    {
        return is_array($this->seen) ? count($this->seen) : 0;
    }

    /**
     * @param list<string> $ids
     */
    private static function page(array $ids, ?string $next): Response
    {
        $items = [];
        foreach ($ids as $id) {
            $items[] = ['id' => $id];
        }

        return self::json(200, ['items' => $items, 'nextCursor' => $next, 'total' => 3]);
    }

    private function body(int $index): mixed
    {
        return json_decode((string) $this->request($index)->getBody(), true);
    }

    public function testARequestCarriesTheKeyAndVersionAndEncodesItsPath(): void
    {
        $this->client(self::json(200, ['id' => 'cus 1']))->customers->get('cus 1/x');
        $request = $this->request(0);
        self::assertSame('https://api.test/v1/customers/cus%201%2Fx', (string) $request->getUri());
        self::assertSame('Bearer gzk_test', $request->getHeaderLine('Authorization'));
        self::assertSame(Goomzo::API_VERSION, $request->getHeaderLine('Goomzo-Version'));
        self::assertFalse($request->hasHeader('Idempotency-Key'));
    }

    public function testAPostIsRetriedAfterA503WithTheSameIdempotencyKey(): void
    {
        $goomzo = $this->client(
            self::json(503, ['code' => 'unavailable', 'message' => 'Down'], ['Retry-After' => '0.01']),
            self::json(201, ['items' => []]),
        );
        $saved = $goomzo->customers->create(['ifExists' => 'update', 'customers' => [['name' => 'Amina', 'phone' => '+255712345678']]]);
        self::assertSame([], $saved);
        self::assertSame(2, $this->made());
        $key = $this->request(0)->getHeaderLine('Idempotency-Key');
        self::assertNotSame('', $key);
        self::assertSame($key, $this->request(1)->getHeaderLine('Idempotency-Key'));
        self::assertSame(['ifExists' => 'update', 'customers' => [['name' => 'Amina', 'phone' => '+255712345678']]], $this->body(1));
    }

    public function testAnInvalidRequestIsAnApiExceptionAndIsNotRetried(): void
    {
        $fields = ['messages[0].to' => ['code' => 'invalid', 'message' => 'Give the country code.']];
        $goomzo = $this->client(self::json(400, ['code' => 'invalid', 'message' => 'Some fields are invalid.', 'fields' => $fields], ['X-Request-Id' => 'req_1']));
        try {
            $goomzo->messages->sendOne(['to' => '0712345678', 'channel' => 'sms', 'content' => ['text' => 'Hi']]);
            self::fail('Expected an ApiException');
        } catch (ApiException $error) {
            self::assertSame([400, 'invalid', 'req_1'], [$error->status, $error->errorCode, $error->requestId]);
            self::assertSame('Give the country code.', $error->fields['messages[0].to']['message']);
        }
        self::assertSame(1, $this->made());
    }

    public function testANetworkFailureIsRetriedThenReported(): void
    {
        $failure = new ConnectException('down', new Request('GET', '/'));
        $goomzo = $this->client($failure, $failure, $failure);
        try {
            $goomzo->segments->list();
            self::fail('Expected a ConnectionException');
        } catch (ConnectionException $error) {
            self::assertFalse($error->timedOut);
        }
        self::assertSame(3, $this->made());
    }

    public function testATemplateIsSentByNameWithItsIdempotencyKey(): void
    {
        $goomzo = $this->client(self::json(201, ['items' => [['id' => 'msg_1', 'private' => true]]]));
        $message = ['to' => '+255712345678', 'template' => 'auth_1', 'variables' => ['code' => '482913']];
        $sent = $goomzo->messages->sendOne($message, idempotencyKey: 'login-1');
        self::assertSame('msg_1', $sent['id']);
        self::assertSame('login-1', $this->request(0)->getHeaderLine('Idempotency-Key'));
        self::assertSame(['messages' => [$message]], $this->body(0));
    }

    public function testAListIsAPageAndLoopingOverItGivesEveryItem(): void
    {
        $first = $this->client(self::page(['a', 'b'], 'c1'), self::page(['c'], null))->customers->list(['segment' => 'seg_1', 'limit' => 2]);
        self::assertSame(['a', 'b'], array_column($first->items, 'id'));
        self::assertSame(3, $first->total);
        self::assertSame(['a', 'b', 'c'], array_column(iterator_to_array($first, false), 'id'));
        self::assertSame('segment=seg_1&limit=2', $this->request(0)->getUri()->getQuery());
        self::assertSame('segment=seg_1&limit=2&cursor=c1', $this->request(1)->getUri()->getQuery());
    }

    public function testAFileComesBackWithItsType(): void
    {
        $media = $this->client(new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg'))->messages->media('rcv_1');
        self::assertSame(['jpeg', 'image/jpeg'], [$media->content, $media->contentType]);
        self::assertSame('/v1/messages/received/rcv_1/media', $this->request(0)->getUri()->getPath());
    }

    public function testADeleteReturnsNothing(): void
    {
        $this->client(new Response(204))->templates->delete('tpl_1');
        self::assertSame('DELETE', $this->request(0)->getMethod());
    }

    public function testTheApiKeyComesFromTheEnvironment(): void
    {
        $before = getenv('GOOMZO_API_KEY');
        putenv('GOOMZO_API_KEY');
        try {
            try {
                new Goomzo();
                self::fail('Expected an InvalidArgumentException');
            } catch (\InvalidArgumentException $error) {
                self::assertStringContainsString('GOOMZO_API_KEY', $error->getMessage());
            }
            putenv('GOOMZO_API_KEY=gzk_env');
            self::assertInstanceOf(Goomzo::class, new Goomzo());
        } finally {
            putenv($before === false ? 'GOOMZO_API_KEY' : "GOOMZO_API_KEY={$before}");
        }
    }
}
