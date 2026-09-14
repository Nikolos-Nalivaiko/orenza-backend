<?php

declare(strict_types=1);

namespace App\Support\Media;

use Carbon\CarbonImmutable;

final readonly class ProcessedImage
{
    /**
     * @param  array<string, string>  $variants
     */
    public function __construct(
        public int $width,
        public int $height,
        public string $color,
        public ?CarbonImmutable $takenAt,
        public array $variants,
    ) {}

    public function variant(string $name): string
    {
        return $this->variants[$name];
    }
}
