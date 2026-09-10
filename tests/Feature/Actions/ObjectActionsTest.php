<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Objects\CreateObjectAction;
use App\Actions\Objects\UpdateObjectAction;
use App\DataTransferObjects\Objects\ObjectData;
use App\Enums\ObjectStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ObjectActionsTest extends TestCase
{
    use RefreshDatabase;

    private function create(): CreateObjectAction
    {
        return app(CreateObjectAction::class);
    }

    private function update(): UpdateObjectAction
    {
        return app(UpdateObjectAction::class);
    }

    public function test_it_creates_an_object_with_the_default_status(): void
    {
        $workspace = Workspace::factory()->create();

        $object = $this->create()->handle($workspace, ObjectData::fromArray([
            'name' => 'ЖК «Пасаж»',
            'address' => 'вул. Стеценка, 12 · Київ',
        ]));

        $this->assertSame(ObjectStatus::Planned, $object->status);
        $this->assertNull($object->client_id);
        $this->assertNull($object->archived_at);
    }

    public function test_it_refuses_a_client_of_another_workspace(): void
    {
        $workspace = Workspace::factory()->create();
        $stranger = Client::factory()->create();

        $this->expectException(BusinessRuleException::class);

        $this->create()->handle($workspace, ObjectData::fromArray([
            'name' => 'ЖК «Пасаж»',
            'address' => 'вул. Стеценка, 12 · Київ',
            'client_id' => $stranger->id,
        ]));
    }

    public function test_it_refuses_to_move_an_object_to_a_foreign_client(): void
    {
        $object = ConstructionObject::factory()->create();
        $stranger = Client::factory()->create();

        $this->expectException(BusinessRuleException::class);

        $this->update()->handle($object, ObjectData::fromArray(['client_id' => $stranger->id]));
    }

    public function test_it_refuses_an_empty_name(): void
    {
        $object = ConstructionObject::factory()->create();

        $this->expectException(BusinessRuleException::class);

        $this->update()->handle($object, ObjectData::fromArray(['name' => '   ']));
    }

    public function test_an_unchanged_archive_flag_does_not_touch_the_object(): void
    {
        $object = ConstructionObject::factory()->create();
        $updated = $object->updated_at;

        $this->travel(1)->minutes();

        $this->update()->handle($object, ObjectData::fromArray(['archived' => false]));

        $this->assertTrue($updated->equalTo($object->refresh()->updated_at));
    }

    public function test_it_archives_and_restores(): void
    {
        $object = ConstructionObject::factory()->create();

        $this->update()->handle($object, ObjectData::fromArray(['archived' => true]));
        $this->assertTrue($object->refresh()->isArchived());

        $this->update()->handle($object, ObjectData::fromArray(['archived' => false]));
        $this->assertFalse($object->refresh()->isArchived());
    }
}
