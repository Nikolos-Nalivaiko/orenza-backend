<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

use App\Http\Requests\ApiFormRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

final class StorePhotoRequest extends ApiFormRequest
{
    public const int MAX_KILOBYTES = 15 * 1024;

    public const int MIN_SIDE = 200;

    public const int MAX_SIDE = 8000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,avif',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                'max:'.self::MAX_KILOBYTES,
                sprintf(
                    'dimensions:min_width=%d,min_height=%d,max_width=%d,max_height=%d',
                    self::MIN_SIDE,
                    self::MIN_SIDE,
                    self::MAX_SIDE,
                    self::MAX_SIDE,
                ),
            ],
            'taken_at' => ['sometimes', 'nullable', 'date', 'after:1990-01-01', 'before_or_equal:tomorrow'],
        ];
    }

    public function photo(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('photo');
    }

    public function takenAt(): ?CarbonImmutable
    {
        $value = $this->input('taken_at');

        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }
}
