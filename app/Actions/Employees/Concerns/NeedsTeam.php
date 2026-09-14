<?php

declare(strict_types=1);

namespace App\Actions\Employees\Concerns;

use App\Exceptions\BusinessRuleException;
use App\Models\Workspace;

trait NeedsTeam
{
    private function guardTeam(Workspace $workspace): void
    {
        if (! $workspace->type->hasTeam()) {
            throw BusinessRuleException::make(__('messages.employees.no_team'));
        }
    }
}
