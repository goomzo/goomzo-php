<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Transport;

/**
 * Saved templates, for SMS, WhatsApp and email. Send one by name with
 * `$goomzo->messages->sendOne(['to' => …, 'template' => 'auth_1', 'variables' => […]])`.
 *
 * @see https://docs.goomzo.africa/docs/developers/php-sdk/templates
 */
final class Templates extends Resource
{
    /**
     * Saves a template. An SMS one is ready at once. A WhatsApp one with `whatsapp.submit` goes to Meta for review,
     * and has a `problem` until Meta approves it.
     *
     *     $goomzo->templates->create([
     *         'name' => 'auth_1',
     *         'channel' => 'sms',
     *         'category' => 'authentication',
     *         'content' => ['text' => 'Your code is {{secret.code}}.'],
     *     ]);
     *
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    public function create(array $template, ?string $idempotencyKey = null): array
    {
        return $this->object($this->transport->request('POST', '/v1/templates', body: $template, idempotencyKey: $idempotencyKey));
    }

    /**
     * Gets a template by its id.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->object($this->transport->request('GET', Transport::path('/v1/templates/{id}', ['id' => $id])));
    }

    /**
     * Lists the templates, by name.
     *
     * @param array{channel?: string, category?: string} $query
     * @return list<array<string, mixed>>
     */
    public function list(array $query = []): array
    {
        return $this->items($this->transport->request('GET', '/v1/templates', query: $query));
    }

    /**
     * Changes a template. What's left out stays as it was.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function update(string $id, array $changes): array
    {
        return $this->object($this->transport->request('PATCH', Transport::path('/v1/templates/{id}', ['id' => $id]), body: $changes));
    }

    /**
     * Deletes a template.
     */
    public function delete(string $id): void
    {
        $this->transport->request('DELETE', Transport::path('/v1/templates/{id}', ['id' => $id]));
    }
}
