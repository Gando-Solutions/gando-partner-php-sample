<?php

require __DIR__ . '/vendor/autoload.php';

use App\AccountTest;
use App\ClientTest;
use App\ConnectTest;
use App\DepositTest;
use App\WebhookTest;

$resources = ['accounts', 'clients', 'deposits', 'webhooks', 'connect'];
$resource = $argv[1] ?? null;

if ($resource === null || !in_array($resource, $resources, true)) {
    try {
        (new AccountTest())->run($resource ?? 'list', $argv[2] ?? null);
    } catch (\InvalidArgumentException $e) {
        printUsage($e->getMessage());
        exit(1);
    }
    exit(0);
}

$action = $argv[2] ?? 'list';
$arg1 = $argv[3] ?? null;
$arg2 = $argv[4] ?? null;

try {
    match ($resource) {
        'accounts' => (new AccountTest())->run($action, $arg1),
        'clients' => (new ClientTest())->run($action, $arg1, $arg2),
        'deposits' => (new DepositTest())->run($action, $arg1, $arg2),
        'webhooks' => (new WebhookTest())->run($action, $arg1, $arg2),
        'connect' => (new ConnectTest())->run($action, $arg1),
    };
} catch (\InvalidArgumentException $e) {
    printUsage($e->getMessage());
    exit(1);
}

function printUsage(string $error): void
{
    fwrite(STDERR, $error . "\n\n");
    fwrite(STDERR, "Usage: php index.php <resource> <action> [args...]\n\n");
    fwrite(STDERR, "Resources:\n");
    fwrite(STDERR, "  accounts  list | list-revoked | list-all | revoke <accountId>\n");
    fwrite(STDERR, "  clients   list [accountId] | create [accountId] | update <clientId> [lastName]\n");
    fwrite(STDERR, "  deposits  list [accountId] | create [accountId] | retrieve <depositId> | delete <depositId>\n");
    fwrite(STDERR, "            update <depositId> [clientId] | get-capture <depositId> | capture <depositId> [amountCents]\n");
    fwrite(STDERR, "            send-deposit-mail <depositId> [email] | send-emails <depositId> [email1,email2]\n");
    fwrite(STDERR, "            cancel <depositId> | get-payment-method <depositId> | get-pdf <depositId> [outputPath]\n");
    fwrite(STDERR, "  webhooks  list | create [url] | update <webhookId> [0|1] | delete <webhookId>\n");
    fwrite(STDERR, "            get-deliveries <webhookId> | get-secret <webhookId> | rotate-secret <webhookId> | test <webhookId>\n");
    fwrite(STDERR, "  connect   signup-url [externalId]\n\n");
    fwrite(STDERR, "Legacy (accounts only): php index.php list\n");
    fwrite(STDERR, "Env: GANDO_API_KEY, GANDO_BASE_URL, GANDO_ACCOUNT_ID, GANDO_WEBHOOK_URL,\n");
    fwrite(STDERR, "     GANDO_CONNECT_SECRET, GANDO_PARTNER_SLUG, GANDO_DASHBOARD_BASE_URL\n");
}
