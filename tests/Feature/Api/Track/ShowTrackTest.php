<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Track;

use App\Enums\MaterialStatus;
use App\Enums\ObjectStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Employee;
use App\Models\Material;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ShowTrackTest extends TestCase
{
    use RefreshDatabase;

    private function track(string $token): TestResponse
    {
        return $this->getJson("/api/v1/track/{$token}");
    }

    public function test_a_guest_opens_the_object_by_its_token(): void
    {
        $object = ConstructionObject::factory()->create([
            'name' => 'ЖК «Пасаж»',
            'address' => 'вул. Стеценка, 12 · Київ',
            'description' => 'Третя черга',
            'started_at' => '2026-06-01',
            'finished_at' => '2026-10-14',
        ]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.name', 'ЖК «Пасаж»')
            ->assertJsonPath('data.address', 'вул. Стеценка, 12 · Київ')
            ->assertJsonPath('data.description', 'Третя черга')
            ->assertJsonPath('data.status.value', 'planned')
            ->assertJsonPath('data.status.label', 'Планується')
            ->assertJsonPath('data.started_at', '2026-06-01')
            ->assertJsonPath('data.finished_at', '2026-10-14')
            ->assertJsonPath('data.readiness', null)
            ->assertJsonPath('data.works.total', 0)
            ->assertJsonPath('data.money.state.value', 'none');
    }

    public function test_internal_data_never_leaves_the_office(): void
    {
        $client = Client::factory()->create(['name' => 'ТОВ «Мегабуд»']);
        $object = ConstructionObject::factory()->forClient($client)->create();

        Material::factory()->ofObject($object)->create(['cost_price' => 120, 'client_price' => 150]);
        $service = Service::factory()->ofObject($object)->create();
        Payment::factory()->ofObject($object)->create(['description' => 'Спитати бухгалтера']);

        $employee = Employee::factory()->create(['workspace_id' => $client->workspace_id]);

        $service->workers()->create(['employee_id' => $employee->id, 'volume' => 10, 'rate' => 400]);

        $response = $this->track($object->public_token)->assertOk();

        $data = $response->json('data');

        foreach (['id', 'workspace_id', 'client', 'public_token', 'discount_percent', 'discount_amount', 'archived_at'] as $key) {
            $this->assertArrayNotHasKey($key, $data);
        }

        foreach (['cost_price', 'client_price', 'buyer', 'approved_by_client'] as $key) {
            $this->assertArrayNotHasKey($key, $data['materials'][0]);
        }

        foreach (['client_price', 'workers'] as $key) {
            $this->assertArrayNotHasKey($key, $data['services'][0]);
        }

        foreach (['name', 'description', 'status', 'client_visible'] as $key) {
            $this->assertArrayNotHasKey($key, $data['payments'][0]);
        }

        $this->assertStringNotContainsString('Мегабуд', (string) $response->getContent());
        $this->assertStringNotContainsString('Спитати бухгалтера', (string) $response->getContent());
    }

    public function test_money_is_counted_the_same_way_as_in_the_office(): void
    {
        $object = ConstructionObject::factory()->create(['discount_percent' => 10]);

        Material::factory()->ofObject($object)->create(['quantity' => 10, 'client_price' => 100, 'status' => MaterialStatus::Delivered]);
        Material::factory()->ofObject($object)->boughtByClient()->create(['quantity' => 50]);
        Service::factory()->ofObject($object)->create(['planned_volume' => 100, 'actual_volume' => null, 'client_price' => 50]);

        Payment::factory()->ofObject($object)->paid()->create(['amount' => 2000]);
        Payment::factory()->ofObject($object)->create(['amount' => 1000, 'paid_at' => '2026-10-01']);
        Payment::factory()->ofObject($object)->status(PaymentStatus::Cancelled)->create(['amount' => 500]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.materials.0.status.value', 'delivered')
            ->assertJsonPath('data.services.0.total', 5000)
            ->assertJsonPath('data.money.client', 5400)
            ->assertJsonPath('data.money.paid', 2000)
            ->assertJsonPath('data.money.due', 3400)
            ->assertJsonPath('data.money.progress', 0.3704)
            ->assertJsonPath('data.money.state.value', 'partial')
            ->assertJsonPath('data.money.state.label', 'Оплачено частково')
            ->assertJsonCount(2, 'data.payments')
            ->assertJsonPath('data.payments.0.received', true)
            ->assertJsonPath('data.payments.1.received', false)
            ->assertJsonPath('data.payments.1.date', '2026-10-01');
    }

    public function test_a_fixed_discount_cannot_exceed_the_total(): void
    {
        $object = ConstructionObject::factory()->create(['discount_amount' => 99999]);

        Service::factory()->ofObject($object)->create(['planned_volume' => 10, 'client_price' => 100]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.money.client', 0)
            ->assertJsonPath('data.money.state.value', 'none');
    }

    public function test_an_overpayment_is_shown_as_such(): void
    {
        $object = ConstructionObject::factory()->create();

        Service::factory()->ofObject($object)->create(['planned_volume' => 10, 'client_price' => 100]);
        Payment::factory()->ofObject($object)->paid()->create(['amount' => 1500]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.money.due', -500)
            ->assertJsonPath('data.money.progress', 1)
            ->assertJsonPath('data.money.state.value', 'over');
    }

    public function test_readiness_follows_the_volume_of_works(): void
    {
        $object = ConstructionObject::factory()->inProgress()->create();

        Service::factory()->ofObject($object)->create([
            'planned_volume' => 100,
            'actual_volume' => 50,
            'status' => ServiceStatus::InProgress,
        ]);
        Service::factory()->ofObject($object)->create([
            'planned_volume' => 100,
            'actual_volume' => null,
            'status' => ServiceStatus::Done,
        ]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.readiness', 0.75)
            ->assertJsonPath('data.works.done', 1)
            ->assertJsonPath('data.works.total', 2)
            ->assertJsonPath('data.finished', false)
            ->assertJsonPath('data.actual_started_at', null);
    }

    public function test_a_finished_object_is_fully_ready_and_shows_actual_dates(): void
    {
        $object = ConstructionObject::factory()->create([
            'status' => ObjectStatus::Done,
            'actual_started_at' => '2026-06-08',
            'actual_finished_at' => '2026-09-30',
        ]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.readiness', 1)
            ->assertJsonPath('data.finished', true)
            ->assertJsonPath('data.actual_started_at', '2026-06-08')
            ->assertJsonPath('data.actual_finished_at', '2026-09-30');
    }

    public function test_a_payment_note_is_shown_only_when_the_owner_allowed_it(): void
    {
        $object = ConstructionObject::factory()->create();

        Payment::factory()->ofObject($object)->create(['name' => 'Аванс за етап', 'client_visible' => true]);
        Payment::factory()->ofObject($object)->create(['name' => 'Спитати бухгалтера', 'client_visible' => false]);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.payments.0.note', 'Аванс за етап')
            ->assertJsonPath('data.payments.1.note', null);
    }

    public function test_an_archived_object_still_opens(): void
    {
        $object = ConstructionObject::factory()->archived()->create(['name' => 'ЖК «Пасаж»']);

        $this->track($object->public_token)
            ->assertOk()
            ->assertJsonPath('data.name', 'ЖК «Пасаж»');
    }

    public function test_an_unknown_token_is_not_found(): void
    {
        ConstructionObject::factory()->create();

        $this->track(ConstructionObject::newPublicToken())
            ->assertNotFound()
            ->assertJsonPath('error_code', 'not_found')
            ->assertJsonPath('message', 'Сторінка недоступна.');
    }

    public function test_a_deleted_object_is_not_found(): void
    {
        $object = ConstructionObject::factory()->create();

        $object->delete();

        $this->track($object->public_token)
            ->assertNotFound()
            ->assertJsonPath('error_code', 'not_found');
    }

    public function test_the_page_is_rate_limited(): void
    {
        $object = ConstructionObject::factory()->create();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->track($object->public_token)->assertOk();
        }

        $this->track($object->public_token)
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'too_many_requests');
    }
}
