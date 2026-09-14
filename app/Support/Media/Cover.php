<?php

declare(strict_types=1);

namespace App\Support\Media;

final readonly class Cover
{
    public const float FOCUS_CENTER = 0.5;

    public function __construct(
        public string $key,
        public int $width,
        public int $height,
        public string $color,
        public float $focusX = self::FOCUS_CENTER,
        public float $focusY = self::FOCUS_CENTER,
    ) {}

    /**
     * @param  array{key: string, width: int, height: int, color: string, focus_x?: float|int, focus_y?: float|int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            width: (int) $data['width'],
            height: (int) $data['height'],
            color: $data['color'],
            focusX: (float) ($data['focus_x'] ?? self::FOCUS_CENTER),
            focusY: (float) ($data['focus_y'] ?? self::FOCUS_CENTER),
        );
    }

    /**
     * @return array{key: string, width: int, height: int, color: string, focus_x: float, focus_y: float}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'width' => $this->width,
            'height' => $this->height,
            'color' => $this->color,
            'focus_x' => $this->focusX,
            'focus_y' => $this->focusY,
        ];
    }

    public function withFocus(float $x, float $y): self
    {
        return new self($this->key, $this->width, $this->height, $this->color, self::clamp($x), self::clamp($y));
    }

    public static function clamp(float $value): float
    {
        return round(max(0.0, min(1.0, $value)), 3);
    }
}
