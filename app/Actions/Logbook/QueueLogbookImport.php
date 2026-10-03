<?php

declare(strict_types=1);

namespace App\Actions\Logbook;

use App\Jobs\Logbook\QRZLogbookImport;
use App\Services\Logbook\LogbookCache;

class QueueLogbookImport
{
    private const QUEUE_CONNECTION = 'redis';

    public function __construct(
        private readonly LogbookCache $cache,
    ) {
    }

    public function queue(): void
    {
        // Drop the cached QRZ payload so the queued job fetches fresh records
        // instead of replaying the cached ones.
        $this->cache->forgetEntries();

        QRZLogbookImport::dispatch()->onConnection(self::QUEUE_CONNECTION);
    }
}
