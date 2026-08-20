<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\InteractsWithEnum;
use App\Enums\Contracts\HasLabel;

enum MembershipRole: string implements HasLabel
{
    use InteractsWithEnum;

    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Владелец',
            self::Admin => 'Администратор',
            self::Member => 'Участник',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Admin => 50,
            self::Member => 10,
        };
    }

    public function isAtLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    public function canManageMembers(): bool
    {
        return $this->isAtLeast(self::Admin);
    }

    public function canManageWorkspace(): bool
    {
        return $this->isAtLeast(self::Admin);
    }

    public function canDeleteWorkspace(): bool
    {
        return $this->isOwner();
    }

    public static function default(): self
    {
        return self::Member;
    }
}
