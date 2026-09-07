<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_ping_answers_without_authentication(): void
    {
        $this->getJson('/api/v1/ping')
            ->assertOk()
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonStructure(['data' => ['name', 'env', 'api_version', 'time']]);
    }

    public function test_allowed_frontend_origin_gets_a_cors_header(): void
    {
        $origin = 'http://localhost:3000';

        config()->set('cors.allowed_origins', [$origin]);

        $this->getJson('/api/v1/ping', ['Origin' => $origin])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', $origin);
    }

    public function test_unknown_origin_is_not_allowed(): void
    {
        config()->set('cors.allowed_origins', ['http://localhost:3000']);

        $allowed = $this->getJson('/api/v1/ping', ['Origin' => 'http://evil.example'])
            ->assertOk()
            ->headers->get('Access-Control-Allow-Origin');

        $this->assertNotSame('http://evil.example', $allowed);
        $this->assertNotSame('*', $allowed);
    }
}
