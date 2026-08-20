<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Support\Str;

final readonly class GenerateWorkspaceSlugAction implements Action
{
    private const int MAX_ATTEMPTS = 50;

    public function __construct(private WorkspaceRepositoryInterface $workspaces) {}

    public function handle(string $name, ?string $desired = null): string
    {
        if ($desired !== null) {
            return $desired;
        }

        $base = Str::slug($name);

        if ($base === '') {
            $base = 'workspace';
        }

        $base = Str::limit($base, 48, '');

        if (! $this->workspaces->slugExists($base)) {
            return $base;
        }

        for ($suffix = 2; $suffix <= self::MAX_ATTEMPTS; $suffix++) {
            $candidate = "{$base}-{$suffix}";

            if (! $this->workspaces->slugExists($candidate)) {
                return $candidate;
            }
        }

        do {
            $candidate = $base.'-'.Str::lower(Str::random(8));
        } while ($this->workspaces->slugExists($candidate));

        return $candidate;
    }
}
