<?php

declare(strict_types=1);

namespace App;

use Gando\Partner\Connect\UrlBuilder;

final class ConnectTest
{
    private readonly UrlBuilder $builder;

    public function __construct(?UrlBuilder $builder = null)
    {
        $this->builder = $builder ?? new UrlBuilder(
            connectSecret: SdkConfig::connectSecret(),
            partnerSlug: SdkConfig::partnerSlug(),
            baseUrl: SdkConfig::BASE_URL,
        );
    }

    public function run(string $action = 'signup-url', ?string $externalId = null): void
    {
        match ($action) {
            'signup-url' => $this->signupUrl($externalId),
            default => throw new \InvalidArgumentException(
                "Unknown connect action: {$action}. Use: signup-url"
            ),
        };
    }

    public function signupUrl(?string $externalId = null): void
    {
        $signupUrl = $this->builder->signupUrl(
            externalId: $externalId ?? 'fleet_acct_' . bin2hex(random_bytes(4)),
            email: 'ops@example.com',
            name: 'Fleetee Ops',
            returnUrl: 'https://partner.example/connect/return',
        );

        ConsoleOutput::heading('Partner Connect signup URL');
        print $signupUrl . "\n";
    }
}
