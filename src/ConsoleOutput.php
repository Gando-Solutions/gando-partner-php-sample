<?php

declare(strict_types=1);

namespace App;

final class ConsoleOutput
{
    public static function heading(string $label): void
    {
        print "=== {$label} ===\n";
    }

    public static function envelope(bool $success, ?string $message = null): void
    {
        print 'Success: ' . ($success ? 'true' : 'false') . "\n";

        if ($message !== null) {
            print "Message: {$message}\n";
        }
    }

    public static function line(string $label, string|int|float|bool $value): void
    {
        print "{$label}: {$value}\n";
    }

    public static function optionalLine(string $label, ?string $value): void
    {
        if ($value !== null && $value !== '') {
            print "{$label}: {$value}\n";
        }
    }

    public static function dateLine(string $label, ?\DateTimeInterface $value, string $format = 'Y-m-d H:i:s'): void
    {
        if ($value !== null) {
            print "{$label}: " . $value->format($format) . "\n";
        }
    }

    public static function blank(): void
    {
        print "\n";
    }
}
