<?php

declare(strict_types=1);

namespace App;

use Gando\Partner\Api\Client;

final class SdkConfig
{
    public const API_KEY = 'gando_pk_seed_fleetee_2026';
    public const BASE_URL = 'http://localhost:3000';
    public const WEBHOOK_URL = 'https://partner.example/webhooks/gando';
    public const CONNECT_SECRET = 'gando_cs_seed_fleetee_2026';
    public const PARTNER_SLUG = 'fleetee';
    public const DASHBOARD_BASE_URL = 'https://dashboard.gando.app';

    public static function client(): Client
    {
        $apiKey = getenv('GANDO_API_KEY');
        $baseUrl = getenv('GANDO_BASE_URL');

        return new Client(
            apiKey: is_string($apiKey) && $apiKey !== '' ? $apiKey : self::API_KEY,
            baseUrl: is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : self::BASE_URL,
        );
    }

    public static function accountId(?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        $id = getenv('GANDO_ACCOUNT_ID');

        if (!is_string($id) || $id === '') {
            throw new \InvalidArgumentException(
                'Account ID required. Set GANDO_ACCOUNT_ID or pass as CLI argument.'
            );
        }

        return $id;
    }

    public static function webhookUrl(?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        $url = getenv('GANDO_WEBHOOK_URL');

        if (!is_string($url) || $url === '') {
            return self::WEBHOOK_URL;
        }

        return $url;
    }

    public static function connectSecret(): string
    {
        $secret = getenv('GANDO_CONNECT_SECRET');

        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        if (self::CONNECT_SECRET !== '') {
            return self::CONNECT_SECRET;
        }

        throw new \InvalidArgumentException(
            'Connect secret required. Set GANDO_CONNECT_SECRET or SdkConfig::CONNECT_SECRET.'
        );
    }

    public static function partnerSlug(): string
    {
        $slug = getenv('GANDO_PARTNER_SLUG');

        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        return self::PARTNER_SLUG;
    }

    public static function dashboardBaseUrl(): string
    {
        $baseUrl = getenv('GANDO_DASHBOARD_BASE_URL');

        if (is_string($baseUrl) && $baseUrl !== '') {
            return $baseUrl;
        }

        return self::DASHBOARD_BASE_URL;
    }
}
