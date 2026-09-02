<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Exceptions\BusinessRuleException;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\FixtureRequest;
use Tests\TestCase;

/**
 * Проверяет каркас API: единый конверт ответа, обработку доменных исключений,
 * формат ошибок валидации и принудительный JSON для api/*.
 */
final class ResponseEnvelopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1')->group(function (): void {
            Route::get('fixture/success', fn () => ApiResponse::success(['id' => 1], 'Готово.'));
            Route::get('fixture/domain-error', fn () => throw BusinessRuleException::make('Так нельзя.', ['reason' => 'demo']));
            Route::post('fixture/validation', fn (FixtureRequest $request) => ApiResponse::success($request->validated()));
        });
    }

    public function test_success_responses_share_one_envelope(): void
    {
        $this->getJson('/api/v1/fixture/success')
            ->assertOk()
            ->assertExactJson([
                'data' => ['id' => 1],
                'message' => 'Готово.',
            ]);
    }

    public function test_domain_exceptions_render_themselves_as_json(): void
    {
        $this->getJson('/api/v1/fixture/domain-error')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'business_rule_violation')
            ->assertJsonPath('message', 'Так нельзя.')
            ->assertJsonPath('errors.reason', 'demo');
    }

    public function test_validation_errors_use_the_same_envelope(): void
    {
        // Без заголовка Accept: ForceJsonResponse обязан всё равно вернуть JSON.
        $this->post('/api/v1/fixture/validation', ['title' => str_repeat('a', 50)])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['message', 'error_code', 'errors' => ['title']]);
    }

    public function test_unknown_api_routes_return_a_json_404(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'not_found');
    }
}
