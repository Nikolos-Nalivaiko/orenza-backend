<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Employees;

use App\Enums\EmployeeStatus;
use App\Models\ConstructionObject;
use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class EmployeesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->company()->ownedBy($this->user)->create();

    }

    private function path(string $tail = '', ?Workspace $workspace = null): string
    {
        $workspace ??= $this->workspace;

        return "/api/v1/workspaces/{$workspace->slug}/employees{$tail}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function add(array $payload = [], ?Workspace $workspace = null): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')->postJson($this->path('', $workspace), [
            'name' => 'Ігор Величко',
            'role' => 'Бригадир',
            'phone' => '+380 67 330 18 42',
            'email' => 'I.Velychko@Orenza.ua',
            ...$payload,
        ]);
    }

    private function personalWorkspace(): Workspace
    {
        return Workspace::factory()->personal()->ownedBy($this->user)->create();
    }

    public function test_a_guest_sees_nothing(): void
    {
        $this->getJson($this->path())->assertUnauthorized();
    }

    public function test_an_employee_is_added(): void
    {
        $this->add()
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ігор Величко')
            ->assertJsonPath('data.role', 'Бригадир')
            ->assertJsonPath('data.phone', '+380673301842')
            ->assertJsonPath('data.email', 'i.velychko@orenza.ua')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.notes', '');

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_only_a_name_is_required(): void
    {
        $this->add(['role' => '', 'phone' => '', 'email' => ''])
            ->assertCreated()
            ->assertJsonPath('data.role', '')
            ->assertJsonPath('data.phone', '');

        $this->assertNull(Employee::sole()->phone);
    }

    public function test_an_employee_is_not_added_without_a_name(): void
    {
        $this->add(['name' => '  '])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['name']]);

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_a_broken_email_is_refused(): void
    {
        $this->add(['email' => 'не пошта'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_a_personal_workspace_has_no_team(): void
    {
        $this->add([], $this->personalWorkspace())
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'business_rule_violation');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_only_the_people_of_this_workspace_are_listed(): void
    {
        Employee::factory()->ofWorkspace($this->workspace)->create(['name' => 'Наш']);
        Employee::factory()->create(['name' => 'Чужий']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Наш')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_list_is_sorted_by_name(): void
    {
        Employee::factory()->ofWorkspace($this->workspace)->create(['name' => 'Ярослав']);
        Employee::factory()->ofWorkspace($this->workspace)->create(['name' => 'Андрій']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path())
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Андрій')
            ->assertJsonPath('data.1.name', 'Ярослав');
    }

    public function test_people_are_filtered_by_status_and_searched(): void
    {
        Employee::factory()->ofWorkspace($this->workspace)->create(['name' => 'Тарас', 'role' => 'Муляр']);
        Employee::factory()->ofWorkspace($this->workspace)->inactive()->create([
            'name' => 'Дмитро',
            'role' => 'Плиточник',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path('?'.http_build_query(['status' => 'inactive'])))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Дмитро')
            ->assertJsonPath('meta.all', 2);

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path('?'.http_build_query(['search' => 'Муляр'])))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Тарас');
    }

    public function test_an_employee_card_opens(): void
    {
        $employee = Employee::factory()->ofWorkspace($this->workspace)->create(['name' => 'Тарас']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path("/{$employee->id}"))
            ->assertOk()
            ->assertJsonPath('data.name', 'Тарас');
    }

    public function test_an_employee_of_another_workspace_is_not_found(): void
    {
        $stranger = Employee::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson($this->path("/{$stranger->id}"))
            ->assertNotFound();
    }

    public function test_contacts_and_a_description_are_saved(): void
    {
        $employee = Employee::factory()->ofWorkspace($this->workspace)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$employee->id}"), [
                'role' => 'Електрик',
                'phone' => '+380 50 214 76 03',
                'notes' => 'Далі 20 км від міста обʼєкти не бере.',
            ])
            ->assertOk()
            ->assertJsonPath('data.role', 'Електрик')
            ->assertJsonPath('data.phone', '+380502147603')
            ->assertJsonPath('data.notes', 'Далі 20 км від міста обʼєкти не бере.');
    }

    public function test_a_person_is_put_on_hold_instead_of_being_erased(): void
    {
        $employee = Employee::factory()->ofWorkspace($this->workspace)->create();

        $this->actingAs($this->user, 'sanctum')
            ->patchJson($this->path("/{$employee->id}"), ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertSame(EmployeeStatus::Inactive, $employee->refresh()->status);
    }

    public function test_an_unused_employee_is_deleted(): void
    {
        $employee = Employee::factory()->ofWorkspace($this->workspace)->create();

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->path("/{$employee->id}"))
            ->assertOk();

        $this->assertSoftDeleted($employee);
    }

    public function test_an_employee_with_charges_is_kept(): void
    {
        $employee = Employee::factory()->ofWorkspace($this->workspace)->create();
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->create();
        $service = Service::factory()->ofObject($object)->create();

        $service->workers()->create(['employee_id' => $employee->id, 'volume' => 10, 'rate' => 100]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->path("/{$employee->id}"))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'business_rule_violation');

        $this->assertNotSoftDeleted($employee);
    }
}
