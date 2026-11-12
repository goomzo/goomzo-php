<?php

declare(strict_types=1);

namespace Goomzo;

use Composer\InstalledVersions;
use Goomzo\Exception\ApiException;
use Goomzo\Exception\ConnectionException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Makes requests: authenticates, pins the API version, retries what's safe to, and throws ApiException.
 *
 * @internal
 */
final class Transport
{
    private const RETRY_STATUSES = [408, 429, 500, 502, 503, 504];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $maxRetries,
        private readonly float $timeout,
        private readonly ClientInterface $http = new Client(),
    ) {}

    /**
     * Fills a path's `{name}` parts, each encoded: `path('/v1/customers/{id}', ['id' => $id])`.
     *
     * @param array<string, string> $params
     */
    public static function path(string $template, array $params): string
    {
        foreach ($params as $name => $value) {
            if ($value === '') {
                throw new \InvalidArgumentException("Missing {$name} for {$template}");
            }
            $template = str_replace('{' . $name . '}', rawurlencode($value), $template);
        }

        return $template;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<mixed>|null $body
     */
    public function request(
        string $method,
        string $route,
        array $query = [],
        ?array $body = null,
        ?string $idempotencyKey = null,
        bool $file = false,
    ): mixed {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => $file ? '*/*' : 'application/json',
            'Goomzo-Version' => Goomzo::API_VERSION,
            'User-Agent' => 'goomzo-php/' . self::version(),
        ];
        if ($method === 'POST') {
            $headers['Idempotency-Key'] = $idempotencyKey ?? self::uuid();
        }
        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::QUERY => self::query($query),
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::HTTP_ERRORS => false,
        ];
        if ($body !== null) {
            $options[RequestOptions::JSON] = $body;
        }

        for ($attempt = 0; ; $attempt++) {
            try {
                $response = $this->http->request($method, $this->baseUrl . $route, $options);
            } catch (ConnectException $error) {
                if ($attempt < $this->maxRetries) {
                    $this->wait(self::backoff($attempt));
                    continue;
                }
                $timedOut = str_contains(strtolower($error->getMessage()), 'timed out');
                throw new ConnectionException(
                    $timedOut ? "No answer within {$this->timeout} s." : "Couldn't reach Goomzo: {$error->getMessage()}",
                    $timedOut,
                    $error,
                );
            } catch (GuzzleException $error) {
                throw new ConnectionException("Couldn't reach Goomzo: {$error->getMessage()}", false, $error);
            }

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return self::result($response, $file);
            }
            $error = self::toError($response);
            // A key still being answered is safe to wait on; any other 409 is about the request itself.
            $retry = in_array($status, self::RETRY_STATUSES, true) || $error->errorCode === 'idempotency_key_in_use';
            if ($retry && $attempt < $this->maxRetries) {
                $this->wait(self::retryAfter($response) ?? self::backoff($attempt));
                continue;
            }
            throw $error;
        }
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, string>
     */
    private static function query(array $query): array
    {
        $out = [];
        foreach ($query as $name => $value) {
            if ($value === null) {
                continue;
            }
            $out[$name] = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) => (string) $value,
                default => throw new \InvalidArgumentException("{$name} must be a string, number or boolean"),
            };
        }

        return $out;
    }

    private static function result(ResponseInterface $response, bool $file): mixed
    {
        if ($response->getStatusCode() === 204) {
            return null;
        }
        $body = (string) $response->getBody();
        if ($file) {
            return new Media($body, $response->getHeaderLine('Content-Type') ?: 'application/octet-stream');
        }

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    private static function toError(ResponseInterface $response): ApiException
    {
        $raw = json_decode((string) $response->getBody(), true);
        $body = is_array($raw) ? $raw : [];
        $status = $response->getStatusCode();
        $code = isset($body['code']) && is_string($body['code']) ? $body['code'] : "http_{$status}";
        $message = isset($body['message']) && is_string($body['message'])
            ? $body['message']
            : "Goomzo answered {$status} {$response->getReasonPhrase()}.";
        /** @var array<string, array{code: string, message: string}> $fields */
        $fields = isset($body['fields']) && is_array($body['fields']) ? $body['fields'] : [];
        $requestId = $response->getHeaderLine('X-Request-Id');

        return new ApiException($status, $code, $message, $fields, $requestId === '' ? null : $requestId);
    }

    /**
     * 0.5 s, 1 s, 2 s… up to 8 s, with jitter so many clients don't retry together.
     */
    private static function backoff(int $attempt): float
    {
        return min(0.5 * 2 ** $attempt, 8.0) * (0.75 + mt_rand() / mt_getrandmax() * 0.5);
    }

    private static function retryAfter(ResponseInterface $response): ?float
    {
        $value = $response->getHeaderLine('Retry-After');
        if (!is_numeric($value)) {
            return null;
        }
        $seconds = (float) $value;

        return $seconds > 0 && $seconds <= 60 ? $seconds : null;
    }

    private function wait(float $seconds): void
    {
        usleep((int) round($seconds * 1_000_000));
    }

    /**
     * This release's version, as Composer installed it.
     */
    private static function version(): string
    {
        $package = 'goomzo/goomzo-php';
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($package)) {
            return InstalledVersions::getPrettyVersion($package) ?? 'unknown';
        }

        return 'unknown';
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
