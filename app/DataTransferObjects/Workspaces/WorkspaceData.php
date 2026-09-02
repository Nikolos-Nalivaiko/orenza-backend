<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workspaces;

use App\DataTransferObjects\BaseData;
use App\Enums\WorkspaceType;

final class WorkspaceData extends BaseData
{
    public function __construct(
        public readonly WorkspaceType $type = WorkspaceType::Personal,
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        $name = isset($attributes['name']) ? trim((string) $attributes['name']) : null;
        $slug = isset($attributes['slug']) ? mb_strtolower(trim((string) $attributes['slug'])) : null;

        return new self(
            type: isset($attributes['type'])
                ? ($attributes['type'] instanceof WorkspaceType ? $attributes['type'] : WorkspaceType::from((string) $attributes['type']))
                : WorkspaceType::default(),
            name: $name === '' ? null : $name,
            slug: $slug === '' ? null : $slug,
        );
    }
}
