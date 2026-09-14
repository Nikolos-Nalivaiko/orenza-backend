<?php

declare(strict_types=1);

namespace Tests\Fixtures;

trait BuildsJpegs
{
    protected function plainJpeg(int $width, int $height): string
    {
        $canvas = imagecreatetruecolor($width, $height);

        imagefilledrectangle($canvas, 0, 0, $width, $height, (int) imagecolorallocate($canvas, 180, 90, 40));

        ob_start();
        imagejpeg($canvas, null, 90);

        return (string) ob_get_clean();
    }

    protected function jpegWithExif(
        int $width,
        int $height,
        int $orientation = 1,
        string $description = '',
        ?string $takenAt = null,
    ): string {
        $text = $description."\0";
        $moment = $takenAt === null ? null : $takenAt."\0";

        $entries = $moment === null ? 2 : 3;
        $ifdEnd = 8 + 2 + $entries * 12 + 4;
        $subIfdOffset = $ifdEnd;
        $subIfdEnd = $moment === null ? $subIfdOffset : $subIfdOffset + 2 + 12 + 4;
        $descriptionOffset = $subIfdEnd;
        $momentOffset = $descriptionOffset + strlen($text);

        $tiff = 'II'.pack('v', 42).pack('V', 8)
            .pack('v', $entries)
            .pack('vvVV', 0x010E, 2, strlen($text), $descriptionOffset)
            .pack('vvVvv', 0x0112, 3, 1, $orientation, 0);

        if ($moment !== null) {
            $tiff .= pack('vvVV', 0x8769, 4, 1, $subIfdOffset);
        }

        $tiff .= pack('V', 0);

        if ($moment !== null) {
            $tiff .= pack('v', 1).pack('vvVV', 0x9003, 2, strlen($moment), $momentOffset).pack('V', 0);
        }

        $tiff .= $text.($moment ?? '');

        $segment = "Exif\0\0".$tiff;
        $jpeg = $this->plainJpeg($width, $height);

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($segment) + 2).$segment.substr($jpeg, 2);
    }
}
