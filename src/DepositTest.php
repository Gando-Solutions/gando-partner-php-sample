<?php

declare(strict_types=1);

namespace App;

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\DepositsListRequest;
use Gando\Partner\Models\Operations\Item;
use Gando\Partner\Models\Operations\PartnerCaptureBody;
use Gando\Partner\Models\Operations\PartnerCautionItem;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;
use Gando\Partner\Models\Operations\PartnerCreateDepositResponse;
use Gando\Partner\Models\Operations\PartnerDepositEmailsBody;
use Gando\Partner\Models\Operations\PartnerPatchDepositBody;
use Gando\Partner\Models\Operations\PartnerSendDepositMailBody;

final class DepositTest
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
            'retrieve' => $this->retrieve(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits retrieve <depositId>'
                ),
            ),
            'delete' => $this->delete(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits delete <depositId>'
                ),
            ),
            'update' => $this->update(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits update <depositId> [clientId]'
                ),
                $arg2,
            ),
            'get-capture' => $this->getCapture(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits get-capture <depositId>'
                ),
            ),
            'capture' => $this->capture(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits capture <depositId> [amountCents]'
                ),
                $arg2,
            ),
            'send-deposit-mail' => $this->sendDepositMail(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits send-deposit-mail <depositId> [email]'
                ),
                $arg2,
            ),
            'send-emails' => $this->sendEmails(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits send-emails <depositId> [email1,email2]'
                ),
                $arg2,
            ),
            'cancel' => $this->cancel(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits cancel <depositId>'
                ),
            ),
            'get-payment-method' => $this->getPaymentMethod(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits get-payment-method <depositId>'
                ),
            ),
            'get-pdf' => $this->getPdf(
                $arg1 ?? throw new \InvalidArgumentException(
                    'Deposit ID required. Usage: php index.php deposits get-pdf <depositId> [outputPath]'
                ),
                $arg2,
            ),
            default => throw new \InvalidArgumentException(
                'Unknown deposits action. Use: list, create, retrieve, delete, update, get-capture, capture, send-deposit-mail, send-emails, cancel, get-payment-method, get-pdf'
            ),
        };
    }

    public function list(?string $accountId = null): void
    {
        $request = new DepositsListRequest(
            accountId: $accountId !== null && $accountId !== '' ? $accountId : null,
            page: 1,
            limit: 20,
        );

        $response = $this->api->deposits->list($request);

        ConsoleOutput::heading('Deposits');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Total', $response->object->data->total);
        ConsoleOutput::blank();

        foreach ($response->object->data->items as $deposit) {
            $this->printDepositItem($deposit);
        }
    }

    public function create(?string $accountId = null): void
    {
        $accountId = SdkConfig::accountId($accountId);

        $body = new PartnerCreateDepositBody(
            accountId: $accountId,
            amount: 800.0,
            rentalContract: 'CTR-' . date('Y') . '-' . random_int(100, 999),
            contractStartAt: '2026-04-01T00:00:00.000Z',
            contractEndAt: '2026-04-10T23:59:59.000Z',
            clientId: null,
            inlineRedirect: true,
            returnUrl: 'https://partner.example/checkout/complete',
        );

        $response = $this->api->deposits->create($body);

        ConsoleOutput::heading('Create deposit');
        $this->printCreateDeposit($response->object->data);
        ConsoleOutput::envelope($response->object->success, $response->object->message);
    }

    public function retrieve(string $depositId): void
    {
        $response = $this->api->deposits->retrieve($depositId);

        ConsoleOutput::heading('Retrieve deposit');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $this->printDepositDetail($response->object->data);
    }

    public function delete(string $depositId): void
    {
        $response = $this->api->deposits->delete($depositId);

        ConsoleOutput::heading('Delete deposit');
        $result = $response->partnerDeleteDepositResponse;
        if ($result === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($result->success);
        ConsoleOutput::line('Outcome', $result->message->value);
    }

    public function update(string $depositId, ?string $clientId = null): void
    {
        $body = new PartnerPatchDepositBody(
            clientId: $clientId,
        );

        $response = $this->api->deposits->update($body, $depositId);

        ConsoleOutput::heading('Update deposit');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Mutation OK', $response->object->data->success ? 'true' : 'false');
    }

    public function getCapture(string $depositId): void
    {
        $response = $this->api->deposits->getCapture($depositId);

        ConsoleOutput::heading('Get capture');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $capture = $response->object->data;
        ConsoleOutput::line('Amount Cents', $capture->amountCents);
        ConsoleOutput::line('Fee Cents', $capture->feeCents);
        ConsoleOutput::line('Net Cents', $capture->netCents);
        ConsoleOutput::line('Status', $capture->status);
        ConsoleOutput::optionalLine('Reason', $capture->reason);
    }

    public function capture(string $depositId, ?string $amountCents = null): void
    {
        $body = new PartnerCaptureBody(
            amount: (int) ($amountCents ?? '10000'),
            reason: 'SDK test capture',
        );

        $response = $this->api->deposits->capture($body, $depositId);

        ConsoleOutput::heading('Capture deposit');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $result = $response->object->data;
        ConsoleOutput::line('Captured Amount', $result->capturedAmount);
        ConsoleOutput::line('Status', $result->status);
    }

    public function sendDepositMail(string $depositId, ?string $email = null): void
    {
        $body = new PartnerSendDepositMailBody(
            email: $email ?? 'tenant@example.com',
        );

        $response = $this->api->deposits->sendDepositMail($body, $depositId);

        ConsoleOutput::heading('Send deposit mail');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $result = $response->object->data;
        ConsoleOutput::line('Link', $result->link);
        ConsoleOutput::line('Email Sent', $result->success ? 'true' : 'false');
        ConsoleOutput::optionalLine('Message ID', $result->messageId);
    }

    public function sendEmails(string $depositId, ?string $emails = null): void
    {
        $recipients = $emails !== null && $emails !== ''
            ? array_map(trim(...), explode(',', $emails))
            : ['tenant@example.com', 'ops@example.com'];

        $body = new PartnerDepositEmailsBody(emails: $recipients);

        $response = $this->api->deposits->sendEmails($body, $depositId);

        ConsoleOutput::heading('Send deposit emails');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Link', $response->object->data->link);

        foreach ($response->object->data->results as $result) {
            ConsoleOutput::line(
                "Recipient {$result->email}",
                $result->success ? 'sent' : 'failed',
            );
        }
    }

    public function cancel(string $depositId): void
    {
        $response = $this->api->deposits->cancel($depositId);

        ConsoleOutput::heading('Cancel deposit');
        if ($response->object === null) {
            ConsoleOutput::line('Status Code', $response->statusCode);
            return;
        }

        ConsoleOutput::envelope($response->object->success, $response->object->message);
        ConsoleOutput::line('Mutation OK', $response->object->data->success ? 'true' : 'false');
    }

    public function getPaymentMethod(string $depositId): void
    {
        $response = $this->api->deposits->getPaymentMethod($depositId);

        ConsoleOutput::heading('Payment method');
        ConsoleOutput::envelope($response->object->success, $response->object->message);
        $method = $response->object->data;
        ConsoleOutput::line('Masked PAN', $method->maskedPan);
        ConsoleOutput::optionalLine('Brand', $method->brand);
        ConsoleOutput::optionalLine('Country', $method->country);
    }

    public function getPdf(string $depositId, ?string $outputPath = null): void
    {
        $response = $this->api->deposits->getPdf($depositId);

        ConsoleOutput::heading('Deposit PDF');
        ConsoleOutput::line('Status Code', $response->statusCode);
        ConsoleOutput::line('Content Type', $response->contentType);

        if ($response->bytes === null) {
            print "No PDF bytes in response.\n";
            return;
        }

        $path = $outputPath ?? __DIR__ . '/../deposit-' . $depositId . '.pdf';
        file_put_contents($path, $response->bytes);
        ConsoleOutput::line('Saved To', $path);
        ConsoleOutput::line('Size Bytes', strlen($response->bytes));
    }

    private function printCreateDeposit(PartnerCreateDepositResponse $deposit): void
    {
        ConsoleOutput::line('Deposit ID', $deposit->id);
        ConsoleOutput::line('Reference', $deposit->reference);
        ConsoleOutput::line('Status', $deposit->status->value);
        ConsoleOutput::line('Amount', $deposit->amount);
        ConsoleOutput::dateLine('Created At', $deposit->createdAt);
        ConsoleOutput::optionalLine('Deposit URL', $deposit->depositUrl);
    }

    private function printDepositItem(Item $deposit): void
    {
        ConsoleOutput::line('Deposit ID', $deposit->id);
        ConsoleOutput::line('Reference', $deposit->reference);
        ConsoleOutput::line('Amount', $deposit->amount);
        ConsoleOutput::line('Status', $deposit->status->value);
        ConsoleOutput::optionalLine('Account ID', $deposit->accountId);
        ConsoleOutput::optionalLine('Client ID', $deposit->clientId);
        ConsoleOutput::optionalLine('Rental Contract', $deposit->rentalContract);
        ConsoleOutput::dateLine('Created At', $deposit->createdAt);
        ConsoleOutput::blank();
    }

    private function printDepositDetail(PartnerCautionItem $deposit): void
    {
        ConsoleOutput::line('Deposit ID', $deposit->id);
        ConsoleOutput::line('Reference', $deposit->reference);
        ConsoleOutput::line('Amount', $deposit->amount);
        ConsoleOutput::line('Status', $deposit->status->value);
        ConsoleOutput::optionalLine('Account ID', $deposit->accountId);
        ConsoleOutput::optionalLine('Client ID', $deposit->clientId);
        ConsoleOutput::optionalLine('Rental Contract', $deposit->rentalContract);
        ConsoleOutput::dateLine('Contract Start', $deposit->contractStartAt);
        ConsoleOutput::dateLine('Contract End', $deposit->contractEndAt);
        ConsoleOutput::dateLine('Created At', $deposit->createdAt);
        ConsoleOutput::dateLine('Updated At', $deposit->updatedAt);
    }
}
