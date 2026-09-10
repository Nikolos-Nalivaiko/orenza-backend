<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Payments;

use App\Enums\PaymentStatus;
use App\Models\ConstructionObject;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    private ConstructionObject $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->company()->ownedBy($this->user)->create();

        Membership::factory()
            ->forWorkspace($this->workspace)
            ->forUser($this->user)
            ->owner()
            ->create();

        $this->object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();
    }

    private function path(string $tail = ''): string
    {
        return "/api/v1/workspaces/{$this->workspace->slug}/objects/{$this->object->id}/payments{$tail}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function add(array $payload = []): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')->postJson($this->path(), [
            'name' => 'Аванс',
            'amount' => 100000,
            'status' => 'pending',
            ...$payload,
        ]);
    }

    public function test_a_guest_sees_nothing(): void
    {
        $this->getJson($this->path())->assertUnauthorized();
    }

    public function test_a_payment_is_added(): void
    {
        $this->add()
            ->assertCreated()
            ->assertJsonPath('data.name', 'Аванс')
            ->assertJsonPath('data.amount', 100000)
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.status.label', 'В очікуванні')
            ->assertJsonPath('data.paid_at', null)
            ->assertJsonPath('data.client_visible', false);
    }

    public function test_a_paid_payment_gets_a_date_even_when_none_was_sent(): void
    {
        $this->add(['status' => 'paid'])
            ->assertCreated()
            ->assertJsonPath('data.status.value', 'paid')
            ->assertJsonPath('data.paid_at', now()->toDateString());
    }

    public function test_a_paid_payment_keeps_the_date_it_was_given(): void
    {
        $this->add(['status' => 'paid', 'paid_at' => '2026-06-12'])
            ->assertCreated()
            ->assertJsonPath('data.paid_at', '2026-06-12');
    }

    public function test_marking_a_payment_as_received_stamps_the_day(): void
    {
        $payment = Payment::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$payment->id}"), ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('data.paid_at', now()->toDateString());
    }

    public function test_taking_a_payment_back_clears_the_day(): void
    {
        $payment = Payment::factory()->ofObject($this->object)->paid()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$payment->id}"), ['status' => 'pending'])
            ->assertOk()
            ->assertJsonPath('data.paid_at', null);

        $this->assertNull($payment->refresh()->paid_at);
    }

    public function test_a_payment_is_edited(): void
    {
        $payment = Payment::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$payment->id}"), [
                'name' => 'Другий транш',
                'amount' => 250000,
                'description' => 'За домовленістю',
                'client_visible' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Другий транш')
            ->assertJsonPath('data.amount', 250000)
            ->assertJsonPath('data.description', 'За домовленістю')
            ->assertJsonPath('data.client_visible', true);
    }

    public function test_a_name_and_an_amount_are_required(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson($this->path(), ['status' => 'pending'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'amount']]);
    }

    public function test_a_negative_amount_is_rejected(): void
    {
        $this->add(['amount' => -1])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['amount']]);
    }

    public function test_a_payment_is_removed(): void
    {
        $payment = Payment::factory()->ofObject($this->object)->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->path("/{$payment->id}"))
            ->assertOk();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_payment_of_another_object_is_not_found(): void
    {
        $stranger = Payment::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$stranger->id}"), ['status' => 'paid'])
            ->assertNotFound();
    }

    public function test_payments_travel_with_the_object(): void
    {
        Payment::factory()->ofObject($this->object)->create(['name' => 'Аванс']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/workspaces/{$this->workspace->slug}/objects/{$this->object->id}")
            ->assertOk()
            ->assertJsonPath('data.payments.0.name', 'Аванс');
    }

    public function test_payments_die_with_the_object(): void
    {
        Payment::factory()->ofObject($this->object)->create();

        $this->object->forceDelete();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_the_statuses_the_finance_tab_offers_are_all_accepted(): void
    {
        foreach (PaymentStatus::values() as $status) {
            $this->add(['status' => $status])->assertCreated()->assertJsonPath('data.status.value', $status);
        }
    }
}
