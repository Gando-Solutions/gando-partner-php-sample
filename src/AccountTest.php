<?php

declare(strict_types=1);

namespace App;

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\AccountsListQueryParamStatus;
use Gando\Partner\Models\Operations\AccountsListResponse;

final class AccountTest
{
    private readonly Client $api;

    public function __construct(?Client $api = null)
    {
        $this->api = $api ?? SdkConfig::client();
    }

    public function run(string $action = 'list', ?string $accountId = null): void
    {
        match ($action) {
            'list' => $this->list(),
            'list-revoked' => $this->listRevoked(),
            'list-all' => $this->listAll(),
            'revoke' => $this->revoke($accountId ?? throw new \InvalidArgumentException(
                'Account ID required. Usage: php index.php accounts revoke <accountId>'
            )),
            default => throw new \InvalidArgumentException(
                "Unknown accounts action: {$action}. Use: list, list-revoked, list-all, revoke"
            ),
        };
    }

    public function list(): void
    {
        $this->printAccounts('Active accounts (default)', $this->api->accounts->list());
    }

    public function listRevoked(): void
    {
        $this->printAccounts(
            'Revoked accounts',
            $this->api->accounts->list(AccountsListQueryParamStatus::Revoked),
        );
    }

    public function listAll(): void
    {
        $this->printAccounts(
            'All accounts',
            $this->api->accounts->list(AccountsListQueryParamStatus::All),
        );
    }

    public function revoke(string $accountId): void
    {
        $response = $this->api->accounts->revoke($accountId);

        ConsoleOutput::heading('Revoke account');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Status', $response->object->data->status->value);
        ConsoleOutput::dateLine('Revoked At', $response->object->data->revokedAt);
    }

    private function printAccounts(string $label, AccountsListResponse $response): void
    {
        ConsoleOutput::heading($label);
        ConsoleOutput::line('Total', $response->object->data->total);
        ConsoleOutput::optionalLine('Message', $response->object->message);
        ConsoleOutput::blank();

        foreach ($response->object->data->accounts as $account) {
            ConsoleOutput::line('Account ID', $account->accountId);
            ConsoleOutput::line('Display Name', $account->displayName);
            ConsoleOutput::line('Email', $account->email);
            ConsoleOutput::line('Company Name', $account->companyName);
            ConsoleOutput::line('Status', $account->status->value);
            ConsoleOutput::dateLine('Linked At', $account->linkedAt);
            ConsoleOutput::line('External ID', $account->externalId);
            ConsoleOutput::blank();
        }
    }
}
