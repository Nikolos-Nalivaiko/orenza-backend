<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

use App\Http\Requests\ApiFormRequest;
use App\Support\Media\Cover;
use Illuminate\Http\UploadedFile;

final class UploadCoverRequest extends ApiFormRequest
{
    public const int MAX_KILOBYTES = 15 * 1024;

    public const int MIN_WIDTH = 320;

    public const int MIN_HEIGHT = 180;

    public const int MAX_SIDE = 8000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cover' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,avif',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                'max:'.self::MAX_KILOBYTES,
                sprintf(
                    'dimensions:min_width=%d,min_height=%d,max_width=%d,max_height=%d',
                    self::MIN_WIDTH,
                    self::MIN_HEIGHT,
                    self::MAX_SIDE,
                    self::MAX_SIDE,
                ),
            ],
            'focus_x' => ['sometimes', 'numeric', 'between:0,1'],
            'focus_y' => ['sometimes', 'numeric', 'between:0,1'],
        ];
    }

    public function cover(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('cover');
    }

    public function focusX(): float
    {
        return (float) $this->input('focus_x', Cover::FOCUS_CENTER);
    }

    public function focusY(): float
    {
        return (float) $this->input('focus_y', Cover::FOCUS_CENTER);
    }
}
