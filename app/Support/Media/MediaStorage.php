<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Jobs\PurgeMediaFiles;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Throwable;

final readonly class MediaStorage
{
    public function __construct(
        private FilesystemManager $filesystem,
        private Repository $config,
    ) {}

    public function diskName(): string
    {
        return (string) $this->config->get('filesystems.media_disk', 'media');
    }

    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }

    /**
     * @param  array<string, string>  $files
     */
    public function store(array $files): void
    {
        $written = [];

        try {
            foreach ($files as $path => $contents) {
                $this->disk()->put($path, $contents, [
                    'visibility' => 'public',
                    'ContentType' => 'image/webp',
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ]);

                $written[] = $path;
            }
        } catch (Throwable $exception) {
            $this->disk()->delete($written);

            throw $exception;
        }
    }

    /**
     * @param  list<string>  $paths
     */
    public function deleteNow(array $paths): void
    {
        $this->disk()->delete($paths);
    }

    /**
     * @param  list<string>  $paths
     */
    public function deleteLater(array $paths): void
    {
        if ($paths !== []) {
            PurgeMediaFiles::dispatch($this->diskName(), $paths);
        }
    }

    public static function newKey(): string
    {
        return bin2hex(random_bytes(12));
    }

    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return $this->filesystem->disk($this->diskName());
    }
}
