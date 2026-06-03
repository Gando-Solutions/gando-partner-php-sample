# Gando Partner PHP SDK — manual test runner

CLI harness to exercise the [`gando/partner`](https://github.com/gando-org/partner-php) SDK against a running Partner API (local or remote).

## Setup

```bash
composer install
```

Ensure the Partner API is reachable (default: `http://localhost:3000`).

## Configuration

Defaults live in `src/SdkConfig.php`. Override with environment variables:

| Variable                   | Used for                                                                |
| -------------------------- | ----------------------------------------------------------------------- |
| `GANDO_API_KEY`            | Partner API key (`gando_pk_…`)                                          |
| `GANDO_BASE_URL`           | API base URL                                                            |
| `GANDO_ACCOUNT_ID`         | Linked rental operator account id (`clients create`, `deposits create`) |
| `GANDO_WEBHOOK_URL`        | Webhook endpoint URL (`webhooks create`)                                |
| `GANDO_CONNECT_SECRET`     | Connect signing secret (`gando_cs_…`, `connect signup-url`)             |
| `GANDO_PARTNER_SLUG`       | Partner slug in signup URLs                                             |
| `GANDO_DASHBOARD_BASE_URL` | Dashboard host for Connect URLs                                         |

Example:

```bash
export GANDO_API_KEY='gando_pk_seed_fleetee_2026'
export GANDO_BASE_URL='http://localhost:3000'
export GANDO_ACCOUNT_ID='acc_…'
export GANDO_CONNECT_SECRET='gando_cs_…'
```

## Usage

```text
php index.php <resource> <action> [args...]
```

Invalid commands print full usage to stderr.

### Accounts

```bash
php index.php accounts list
php index.php accounts list-revoked
php index.php accounts list-all
php index.php accounts revoke <accountId>
```

### Clients

```bash
php index.php clients list [accountId]
php index.php clients create [accountId]
php index.php clients update <clientId> [lastName]
```

### Deposits

```bash
php index.php deposits list [accountId]
php index.php deposits create [accountId]
php index.php deposits retrieve <depositId>
php index.php deposits delete <depositId>
php index.php deposits update <depositId> [clientId]
php index.php deposits get-capture <depositId>
php index.php deposits capture <depositId> [amountCents]
php index.php deposits send-deposit-mail <depositId> [email]
php index.php deposits send-emails <depositId> [email1,email2]
php index.php deposits cancel <depositId>
php index.php deposits get-payment-method <depositId>
php index.php deposits get-pdf <depositId> [outputPath]
```

`capture` amount is in **EUR cents** (default: `10000` = €100.00).  
`get-pdf` writes a file (default: `deposit-<id>.pdf` in the project root).

### Webhooks

```bash
php index.php webhooks list
php index.php webhooks create [url]
php index.php webhooks update <webhookId> [0|1]
php index.php webhooks delete <webhookId>
php index.php webhooks get-deliveries <webhookId>
php index.php webhooks get-secret <webhookId>
php index.php webhooks rotate-secret <webhookId>
php index.php webhooks test <webhookId>
```

`update` optional second argument: `1` = active, `0` = inactive.  
`create` and `rotate-secret` print the signing secret **once** — store it securely.

### Connect (signup URL builder)

Does not call the HTTP API; builds a signed Partner Connect registration URL.

```bash
php index.php connect signup-url [externalId]
```

Requires `GANDO_CONNECT_SECRET` (or `SdkConfig::CONNECT_SECRET`).

## Legacy shorthand (accounts only)

These still work and map to account actions:

```bash
php index.php list
php index.php list-revoked
php index.php list-all
php index.php revoke <accountId>
```

## Project layout

| Path                    | Role                                  |
| ----------------------- | ------------------------------------- |
| `index.php`             | CLI entrypoint                        |
| `src/AccountTest.php`   | Accounts SDK methods                  |
| `src/ClientTest.php`    | Clients SDK methods                   |
| `src/DepositTest.php`   | Deposits SDK methods                  |
| `src/WebhookTest.php`   | Webhooks SDK methods                  |
| `src/ConnectTest.php`   | `UrlBuilder::signupUrl()`             |
| `src/SdkConfig.php`     | Shared credentials and client factory |
| `src/ConsoleOutput.php` | Formatted stdout helpers              |

## Destructive actions

Use with care on non-seed environments:

- `accounts revoke` — disconnects a linked rental operator
- `deposits delete`, `capture`, `cancel` — mutates deposit state
- `webhooks delete`, `rotate-secret` — removes or invalidates webhook configuration

Prefer IDs from a prior `list` or `retrieve` call.

## SDK reference

Official SDK docs ship with the package: `vendor/gando/partner/README.md` and `vendor/gando/partner/docs/sdks/`.
