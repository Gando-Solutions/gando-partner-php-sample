<?php

declare(strict_types=1);

namespace App;

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\CreatePartnerWebhookSubscriptionBody;
use Gando\Partner\Models\Operations\UpdatePartnerWebhookSubscriptionBody;
use Gando\Partner\Models\Operations\WebhooksListV1WebhookSubscriptionItem;

final class WebhookTest
{
    private readonly Client $api;

    public function __construct(?Client $api = null)
    {
        $this->api = $api ?? SdkConfig::client();
    }

    public function run(string $action = 'list', ?string $arg1 = null, ?string $arg2 = null): void
    {
        match ($action) {
            'list' => $this->list(),
            'create' => $this->create($arg1),
            'update' => $this->update(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Webhook ID required. Usage: php index.php webhooks update <webhookId> [0|1]'
                ),
                $arg2,
            ),
            'delete' => $this->delete(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Webhook ID required. Usage: php index.php webhooks delete <webhookId>'
                ),
            ),
            'get-deliveries' => $this->getDeliveries(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Webhook ID required. Usage: php index.php webhooks get-deliveries <webhookId>'
                ),
            ),
            'get-secret' => $this->getSecret(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Webhook ID required. Usage: php index.php webhooks get-secret <webhookId>'
                ),
            ),
            'rotate-secret' => $this->rotateSecret(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Webhook ID required. Usage: php index.php webhooks rotate-secret <webhookId>'
                ),
            ),
            'test' => $this->test(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Webhook ID required. Usage: php index.php webhooks test <webhookId>'
                ),
            ),
            default => throw new \InvalidArgumentException(
                'Unknown webhooks action. Use: list, create, update, delete, get-deliveries, get-secret, rotate-secret, test'
            ),
        };
    }

    public function list(): void
    {
        $response = $this->api->webhooks->list(page: 1, limit: 20);

        ConsoleOutput::heading('Webhooks');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Total', $response->object->data->total);
        ConsoleOutput::blank();

        foreach ($response->object->data->items as $webhook) {
            $this->printWebhook($webhook);
        }
    }

    public function create(?string $url = null): void
    {
        $body = new CreatePartnerWebhookSubscriptionBody(
            url: SdkConfig::webhookUrl($url),
        );

        $response = $this->api->webhooks->create($body);

        ConsoleOutput::heading('Create webhook');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $webhook = $response->object->data;
        ConsoleOutput::line('Webhook ID', $webhook->id);
        ConsoleOutput::line('URL', $webhook->url);
        ConsoleOutput::line('Secret', $webhook->secret);
        ConsoleOutput::dateLine('Created At', $webhook->createdAt);
        $this->printEvents($webhook->events);
    }

    public function update(string $webhookId, ?string $isActive = null): void
    {
        $body = new UpdatePartnerWebhookSubscriptionBody(
            isActive: $isActive === null ? null : $isActive === '1',
        );

        $response = $this->api->webhooks->update($body, $webhookId);

        ConsoleOutput::heading('Update webhook');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $webhook = $response->object->data;
        ConsoleOutput::line('Webhook ID', $webhook->id);
        ConsoleOutput::line('URL', $webhook->url);
        ConsoleOutput::line('Active', $webhook->isActive ? 'true' : 'false');
        $this->printEvents($webhook->events);
    }

    public function delete(string $webhookId): void
    {
        $response = $this->api->webhooks->delete($webhookId);

        ConsoleOutput::heading('Delete webhook');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Deleted', $response->object->data->deleted ? 'true' : 'false');
    }

    public function getDeliveries(string $webhookId): void
    {
        $response = $this->api->webhooks->getDeliveries($webhookId);

        ConsoleOutput::heading('Webhook deliveries');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::blank();

        foreach ($response->object->data as $delivery) {
            ConsoleOutput::line('Delivery ID', $delivery->id);
            ConsoleOutput::line('Event Type', $delivery->eventType->value);
            ConsoleOutput::line('Attempt', $delivery->attemptCount);
            ConsoleOutput::dateLine('Created At', $delivery->createdAt);
            if ($delivery->statusCode !== null) {
                ConsoleOutput::line('Status Code', $delivery->statusCode);
            }
            ConsoleOutput::optionalLine('Error', $delivery->error);
            ConsoleOutput::dateLine('Delivered At', $delivery->deliveredAt);
            ConsoleOutput::dateLine('Next Retry At', $delivery->nextRetryAt);
            ConsoleOutput::blank();
        }
    }

    public function getSecret(string $webhookId): void
    {
        $response = $this->api->webhooks->getSecret($webhookId);

        ConsoleOutput::heading('Webhook secret');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Secret', $response->object->data->secret);
    }

    public function rotateSecret(string $webhookId): void
    {
        $response = $this->api->webhooks->rotateSecret($webhookId);

        ConsoleOutput::heading('Rotate webhook secret');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Secret', $response->object->data->secret);
    }

    public function test(string $webhookId): void
    {
        $response = $this->api->webhooks->test($webhookId);

        ConsoleOutput::heading('Test webhook');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Endpoint Status Code', $response->object->data->statusCode);
    }

    private function printWebhook(WebhooksListV1WebhookSubscriptionItem $webhook): void
    {
        ConsoleOutput::line('Webhook ID', $webhook->id);
        ConsoleOutput::line('URL', $webhook->url);
        ConsoleOutput::line('Active', $webhook->isActive ? 'true' : 'false');
        ConsoleOutput::dateLine('Created At', $webhook->createdAt);
        ConsoleOutput::dateLine('Updated At', $webhook->updatedAt);
        $this->printEvents($webhook->events);
        ConsoleOutput::blank();
    }

    /**
     * @param  array<\BackedEnum>  $events
     */
    private function printEvents(array $events): void
    {
        $labels = array_map(
            static fn(\BackedEnum $event): string => (string) $event->value,
            $events,
        );
        ConsoleOutput::line('Events', $labels === [] ? '(default)' : implode(', ', $labels));
    }
}
