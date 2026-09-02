<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FixtureStatus;

final class InteractsWithEnumTest extends TestCase
{
    public function test_it_exposes_values_and_names(): void
    {
        $this->assertSame(['draft', 'published'], FixtureStatus::values());
        $this->assertSame(['Draft', 'Published'], FixtureStatus::names());
    }

    public function test_options_use_the_label_contract(): void
    {
        $this->assertSame(
            [
                ['value' => 'draft', 'label' => 'Черновик'],
                ['value' => 'published', 'label' => 'Опубликовано'],
            ],
            FixtureStatus::options(),
        );
    }

    public function test_it_resolves_cases_by_name_case_insensitively(): void
    {
        $this->assertSame(FixtureStatus::Published, FixtureStatus::tryFromName('published'));
        $this->assertSame(FixtureStatus::Draft, FixtureStatus::tryFromName('DRAFT'));
        $this->assertNull(FixtureStatus::tryFromName('unknown'));
    }

    public function test_it_helps_with_membership_checks(): void
    {
        $this->assertTrue(FixtureStatus::Draft->in(FixtureStatus::Draft, FixtureStatus::Published));
        $this->assertTrue(FixtureStatus::Draft->notIn(FixtureStatus::Published));
    }
}
