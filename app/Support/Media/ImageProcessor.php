<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Support\Media\Contracts\ImageVariant;
use Carbon\CarbonImmutable;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\ImageManager;

final readonly class ImageProcessor
{
    private const array EXIF_DATE_TAGS = ['DateTimeOriginal', 'DateTimeDigitized', 'DateTime'];

    private const int EARLIEST_YEAR = 1990;

    public function __construct(private ImageManager $images) {}

    /**
     * @param  list<ImageVariant>  $variants
     */
    public function process(string $path, array $variants): ProcessedImage
    {
        $takenAt = $this->takenAt($path);

        try {
            $image = $this->images->read($path);
        } catch (RuntimeException $exception) {
            throw new UnreadableImageException($exception->getMessage(), previous: $exception);
        }

        $width = $image->width();
        $height = $image->height();
        $encoded = [];

        usort($variants, static fn (ImageVariant $a, ImageVariant $b): int => $b->maxSide() <=> $a->maxSide());

        foreach ($variants as $variant) {
            $image->scaleDown($variant->maxSide(), $variant->maxSide());

            $encoded[(string) $variant->value] = $image->toWebp(quality: $variant->quality())->toString();
        }

        $color = substr($image->resize(1, 1)->pickColor(0, 0)->toHex('#'), 0, 7);

        return new ProcessedImage($width, $height, $color, $takenAt, $encoded);
    }

    private function takenAt(string $path): ?CarbonImmutable
    {
        if (! function_exists('exif_read_data') || ! in_array(@exif_imagetype($path), [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM], true)) {
            return null;
        }

        $data = @exif_read_data($path);

        if (! is_array($data)) {
            return null;
        }

        foreach (self::EXIF_DATE_TAGS as $tag) {
            $value = $data[$tag] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $moment = CarbonImmutable::createFromFormat('Y:m:d H:i:s', trim($value));

            if ($moment instanceof CarbonImmutable && $moment->year >= self::EARLIEST_YEAR && $moment->lessThanOrEqualTo(CarbonImmutable::now()->addDay())) {
                return $moment;
            }
        }

        return null;
    }
}
