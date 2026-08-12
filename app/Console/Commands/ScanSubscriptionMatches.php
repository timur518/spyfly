<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SubscriptionFlightScanner;
use Illuminate\Console\Command;

class ScanSubscriptionMatches extends Command
{
    protected $signature = 'subscriptions:scan {--force : Ignore the scan interval throttle}';

    protected $description = 'Scan active subscriptions and refresh matched flights';

    public function handle(SubscriptionFlightScanner $scanner): int
    {
        $report = $scanner->scanDueSubscriptions((bool) $this->option('force'));

        if (($report['skipped'] ?? false) === true) {
            $this->info(sprintf(
                'Skipped: last scan was less than %d minute(s) ago.',
                (int) ($report['interval_minutes'] ?? 60),
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Scanned %d subscription(s), updated %d, matched %d flight(s), errors %d.',
            (int) ($report['subscriptions'] ?? 0),
            (int) ($report['updated'] ?? 0),
            (int) ($report['matched'] ?? 0),
            (int) ($report['errors'] ?? 0),
        ));

        return self::SUCCESS;
    }
}
