<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SearchLog;

class FlightSearchCompleted
{
    public ?SearchLog $searchLog = null;
    public bool $searchLogStored = false;
    public bool $signalsStored = false;

    public function __construct(
        public array $searchParams,
        public array $payload,
        public ?int $userId = null,
    ) {
    }
}
