<?php

namespace App\Console\Commands;

use App\Services\WarehousePhotoStorage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('warehouse-photos:cleanup {--limit=100 : Maximum records per run}')]
#[Description('Retry tracked photo deletion and remove expired temporary uploads')]
class CleanupWarehousePhotos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WarehousePhotoStorage $photos): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $this->info('Processed: '.$photos->cleanup($limit));

        return self::SUCCESS;
    }
}
