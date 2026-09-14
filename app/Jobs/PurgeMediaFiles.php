<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Queue\Queueable;

final class PurgeMediaFiles implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 30;

    /**
     * @param  list<string>  $paths
     */
    public function __construct(
        public readonly string $disk,
        public readonly array $paths,
    ) {}

    public function handle(FilesystemManager $filesystem): void
    {
        $filesystem->disk($this->disk)->delete($this->paths);
    }
}
