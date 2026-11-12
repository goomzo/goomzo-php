<?php

declare(strict_types=1);

namespace Goomzo;

use Goomzo\Resources\Activity;
use Goomzo\Resources\Broadcasts;
use Goomzo\Resources\Customers;
use Goomzo\Resources\Messages;
use Goomzo\Resources\Segments;
use Goomzo\Resources\Templates;
use Goomzo\Resources\WebhookEndpoints;
use Goomzo\Resources\WhatsApp;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;

/**
 * The Goomzo API client. Make one and share it across your app.
 *
 *     use Goomzo\Goomzo;
 *
 *     $goomzo = new Goomzo(); // reads GOOMZO_API_KEY
 *     $goomzo->messages->sendOne(['to' => '+255712345678', 'channel' => 'sms', 'content' => ['text' => 'Hi']]);
 *
 * Requests and answers are arrays with the API's own keys, exactly as in its JSON.
 *
 * @see https://docs.goomzo.africa/docs/developers/php-sdk
 */
final class Goomzo
{
    /** The API version this release is written for. Every request is sent with it, as `Goomzo-Version`. */
    public const API_VERSION = '2026-11-08';

    /** Send SMS and WhatsApp messages, each to one person. */
    public readonly Messages $messages;

    /** Send one message to many people. */
    public readonly Broadcasts $broadcasts;

    /** Saved templates, sent by name. */
    public readonly Templates $templates;

    /** The business's WhatsApp account and its templates. */
    public readonly WhatsApp $whatsapp;

    /** Add and update customers. */
    public readonly Customers $customers;

    /** Record customer activity, by phone, email or id. */
    public readonly Activity $activity;

    /** Groups of customers. */
    public readonly Segments $segments;

    /** URLs that hear webhook events. */
    public readonly WebhookEndpoints $webhookEndpoints;

    /**
     * @param string|null $apiKey Your API key's secret, `gzk_…`. Defaults to the GOOMZO_API_KEY environment variable.
     *                            Keep it on your server.
     * @param int $maxRetries How many times to try again after a network error, a 429 or a 5xx.
     * @param float $timeout Seconds to wait for each try.
     * @param ClientInterface|null $httpClient Your own Guzzle client, such as for a proxy or tests.
     */
    public function __construct(
        ?string $apiKey = null,
        string $baseUrl = 'https://api.goomzo.africa',
        int $maxRetries = 2,
        float $timeout = 60.0,
        ?ClientInterface $httpClient = null,
    ) {
        $key = $apiKey ?? (getenv('GOOMZO_API_KEY') ?: null);
        if ($key === null || $key === '') {
            throw new InvalidArgumentException('Give an apiKey, or set GOOMZO_API_KEY.');
        }
        $transport = new Transport($key, rtrim($baseUrl, '/'), $maxRetries, $timeout, $httpClient ?? new Client());

        $this->messages = new Messages($transport);
        $this->broadcasts = new Broadcasts($transport);
        $this->templates = new Templates($transport);
        $this->whatsapp = new WhatsApp($transport);
        $this->customers = new Customers($transport);
        $this->activity = new Activity($transport);
        $this->segments = new Segments($transport);
        $this->webhookEndpoints = new WebhookEndpoints($transport);
    }
}
