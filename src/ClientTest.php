<?php

declare(strict_types=1);

namespace App;

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Components\ParticulierClient;
use Gando\Partner\Models\Components\ParticulierClientClientType;
use Gando\Partner\Models\Components\ParticulierPartnerClientPatch;
use Gando\Partner\Models\Operations\ClientsCreateResponse;
use Gando\Partner\Models\Operations\ClientsListPartnerClientItem;

final class ClientTest
{
    private readonly Client $api;

    public function __construct(?Client $api = null)
    {
        $this->api = $api ?? SdkConfig::client();
    }

    public function run(string $action = 'list', ?string $arg1 = null, ?string $arg2 = null): void
    {
        match ($action) {
            'list' => $this->list($arg1),
            'create' => $this->create($arg1),
            'update' => $this->update(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Client ID required. Usage: php index.php clients update <clientId>'
                ),
                $arg2,
            ),
            default => throw new \InvalidArgumentException(
                "Unknown clients action: {$action}. Use: list, create, update"
            ),
        };
    }

    public function list(?string $accountId = null): void
    {
        $response = $this->api->clients->list(
            accountId: $accountId !== null && $accountId !== '' ? $accountId : null,
            page: 1,
            limit: 20,
        );

        ConsoleOutput::heading('Clients');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Page', (int) $response->object->data->pagination->page);
        ConsoleOutput::line('Limit', (int) $response->object->data->pagination->limit);
        ConsoleOutput::line('Total', (int) $response->object->data->pagination->total);
        ConsoleOutput::blank();

        foreach ($response->object->data->items as $client) {
            $this->printClient($client);
        }
    }

    public function create(?string $accountId = null): void
    {
        $accountId = SdkConfig::accountId($accountId);

        $body = new ParticulierClient(
            firstName: 'Jean',
            lastName: 'Dupont',
            email: 'tenant-' . bin2hex(random_bytes(4)) . '@example.com',
            clientType: ParticulierClientClientType::Particulier,
            accountId: $accountId,
        );

        $response = $this->api->clients->create($body);

        ConsoleOutput::heading('Create client');
        $this->printCreateResponse($response);
    }

    public function update(string $clientId, ?string $lastName = null): void
    {
        $body = new ParticulierPartnerClientPatch(
            lastName: $lastName ?? 'Martin',
        );

        $response = $this->api->clients->update($body, $clientId);

        ConsoleOutput::heading('Update client');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Client ID', $response->object->data->id);
        ConsoleOutput::line('Last Name', $response->object->data->lastName);
    }

    private function printCreateResponse(ClientsCreateResponse $response): void
    {
        $body = $response->twoHundredAndOneApplicationJsonObject
            ?? $response->twoHundredApplicationJsonObject;

        if ($body === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($body->success, $body->message);
        ConsoleOutput::line('Client ID', $body->data->id);
        ConsoleOutput::line('HTTP Status', $response->statusCode);
    }

    private function printClient(ClientsListPartnerClientItem $client): void
    {
        ConsoleOutput::line('Client ID', $client->id);
        ConsoleOutput::line('Email', $client->email);
        ConsoleOutput::line('First Name', $client->firstName);
        ConsoleOutput::line('Last Name', $client->lastName);
        ConsoleOutput::line('Client Type', $client->clientType->value);
        ConsoleOutput::line('Archived', $client->isArchived ? 'true' : 'false');
        ConsoleOutput::dateLine('Created At', $client->createdAt);
        ConsoleOutput::dateLine('Updated At', $client->updatedAt);
        ConsoleOutput::optionalLine('Phone', $client->phone);
        ConsoleOutput::optionalLine('Company Name', $client->companyName);
        ConsoleOutput::blank();
    }
}
