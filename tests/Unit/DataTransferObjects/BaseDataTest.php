<?php

declare(strict_types=1);

namespace Tests\Unit\DataTransferObjects;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FixtureData;

final class BaseDataTest extends TestCase
{
    public function test_omitted_properties_are_stripped_from_the_payload(): void
    {
        $data = FixtureData::fromArray(['age' => 30]);

        $this->assertSame(['age' => 30], $data->toArray());
        $this->assertFalse($data->has('firstName'));
        $this->assertTrue($data->has('age'));
    }

    public function test_provided_properties_are_snake_cased(): void
    {
        $data = FixtureData::fromArray(['first_name' => 'Ада', 'age' => 36]);

        $this->assertSame(['first_name' => 'Ада', 'age' => 36], $data->toArray());
    }

    public function test_null_differs_from_a_missing_value(): void
    {
        $data = FixtureData::fromArray([]);

        // age передан как null и остаётся в payload, first_name — не передан вовсе.
        $this->assertSame(['age' => null], $data->toArray());
    }

    public function test_only_and_except_filter_the_payload(): void
    {
        $data = FixtureData::fromArray(['first_name' => 'Ада', 'age' => 36]);

        $this->assertSame(['first_name' => 'Ада'], $data->only('first_name'));
        $this->assertSame(['age' => 36], $data->except('first_name'));
    }

    public function test_it_is_json_serialisable(): void
    {
        $data = FixtureData::fromArray(['first_name' => 'Ада', 'age' => 36]);

        $this->assertSame('{"first_name":"Ада","age":36}', json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
