<?php

declare(strict_types=1);

namespace App\Support\Media\Contracts;

use BackedEnum;

interface ImageVariant extends BackedEnum
{
    public function maxSide(): int;

    public function quality(): int;
}
