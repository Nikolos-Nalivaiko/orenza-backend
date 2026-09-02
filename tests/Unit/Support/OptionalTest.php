<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Optional;
use PHPUnit\Framework\TestCase;

final class OptionalTest extends TestCase
{
    public function test_it_distinguishes_missing_from_null(): void
    {
        $this->assertTrue(Optional::isMissing(new Optional));
        $this->assertTrue(Optional::isMissing(Optional::create()));

        $this->assertFalse(Optional::isMissing(null));
        $this->assertTrue(Optional::isPresent(null));
        $this->assertTrue(Optional::isPresent(''));
    }

    public function test_create_returns_a_shared_instance(): void
    {
        $this->assertSame(Optional::create(), Optional::create());
    }

    public function test_it_serialises_to_null(): void
    {
        $this->assertNull((new Optional)->jsonSerialize());
    }
}
