# goomzo-php

The [Goomzo API](https://docs.goomzo.africa/docs/developers/php-sdk) for PHP: send SMS and WhatsApp messages, send
saved templates, keep customers in step, and check webhooks. PHP 8.1 or later.

```bash
composer require goomzo/goomzo-php
```

```php
use Goomzo\Goomzo;

$goomzo = new Goomzo(); // reads GOOMZO_API_KEY

// One message.
$goomzo->messages->sendOne(['to' => '+255712345678', 'channel' => 'sms', 'content' => ['text' => 'Your order is ready.']]);

// A saved template. {{secret.code}} is delivered, but kept as ••••••.
$goomzo->messages->sendOne(['to' => '+255712345678', 'template' => 'auth_1', 'variables' => ['code' => '482913']]);

// Add or update a customer, with their segments and activity.
$goomzo->customers->create([
    'ifExists' => 'update',
    'customers' => [['name' => 'Amina Juma', 'phone' => '+255712345678', 'segments' => [['name' => 'Shoppers']]]],
]);

// Every customer in a segment, a page at a time behind the scenes.
foreach ($goomzo->segments->customers->list('seg_4hq2v9n1c8x0t6pk') as $customer) {
    echo $customer['name'];
}
```

## What it does for you

- **The API's own shapes:** requests and answers are arrays with the API's keys, exactly as in its JSON.
- **A fixed version:** it pins the API version it was written for.
- **Safe retries:** network errors, 429 and 5xx are tried again, twice by default. Every POST carries an
  `Idempotency-Key`, the same on each try, so nothing is done twice.
- **Lists page themselves:** loop over a list for every item.
- **Errors you can act on:** an `ApiException` has the `status`, the `errorCode`, the `fields` that were wrong and the
  `requestId`.

## Webhooks

```php
use Goomzo\Webhook;

$event = Webhook::verify(file_get_contents('php://input'), getallheaders(), getenv('GOOMZO_WEBHOOK_SECRET'));
if ($event['type'] === 'message.delivered') {
    // …
}
```

## Issues

This repository is published from Goomzo's main repository on each release. For questions and issues, write to
[hello@goomzo.africa](mailto:hello@goomzo.africa).
