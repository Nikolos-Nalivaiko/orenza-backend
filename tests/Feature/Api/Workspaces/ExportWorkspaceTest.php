<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Workspaces;

use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Employee;
use App\Models\Material;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceWorker;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

final class ExportWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->company()->ownedBy($this->user)->create(['slug' => 'budmaister']);

    }

    public function test_the_summary_counts_the_workspace_data(): void
    {
        $client = Client::factory()->ofWorkspace($this->workspace)->create();
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->forClient($client)->create();
        $archived = ConstructionObject::factory()->ofWorkspace($this->workspace)->archived()->create();
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create()->delete();
        Material::factory()->ofObject($object)->count(2)->create();
        Service::factory()->ofObject($archived)->create();
        Payment::factory()->ofObject($object)->count(3)->create();
        Employee::factory()->ofWorkspace($this->workspace)->create();

        ConstructionObject::factory()->create();

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/workspaces/budmaister/export/summary')
            ->assertOk()
            ->assertExactJson(['data' => [
                'objects' => 2,
                'archived' => 1,
                'clients' => 1,
                'employees' => 1,
                'materials' => 2,
                'services' => 1,
                'payments' => 3,
            ]]);
    }

    public function test_a_personal_workspace_has_no_team_in_the_summary(): void
    {
        $personal = Workspace::factory()->personal()->ownedBy($this->user)->create(['slug' => 'personal-one']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/workspaces/personal-one/export/summary')
            ->assertOk()
            ->assertJsonPath('data.employees', null);
    }

    public function test_the_archive_contains_csv_files_and_a_json_copy(): void
    {
        $client = Client::factory()->ofWorkspace($this->workspace)->create(['name' => 'ТОВ Замовник']);
        $object = ConstructionObject::factory()->ofWorkspace($this->workspace)->forClient($client)->archived()->create(['name' => 'Квартира на Липовій']);
        $employee = Employee::factory()->ofWorkspace($this->workspace)->create(['name' => 'Петро']);
        $service = Service::factory()->ofObject($object)->create(['name' => 'Штукатурка']);
        ServiceWorker::query()->create(['service_id' => $service->id, 'employee_id' => $employee->id, 'volume' => 12.5, 'rate' => 150]);
        Material::factory()->ofObject($object)->create(['name' => 'Цемент']);
        Payment::factory()->ofObject($object)->create(['name' => 'Аванс', 'amount' => 1234.5]);

        $response = $this->download()->assertOk();

        $this->assertStringContainsString('orenza-budmaister-'.now()->toDateString().'.zip', (string) $response->headers->get('Content-Disposition'));

        $files = $this->unzip($response);

        $this->assertSame(
            ['objects.csv', 'clients.csv', 'materials.csv', 'services.csv', 'payments.csv', 'employees.csv', 'data.json'],
            array_keys($files),
        );

        $this->assertStringStartsWith("\u{FEFF}ID;", $files['objects.csv']);
        $this->assertStringContainsString('Квартира на Липовій', $files['objects.csv']);
        $this->assertStringContainsString('ТОВ Замовник', $files['objects.csv']);
        $this->assertStringContainsString('Цемент', $files['materials.csv']);
        $this->assertStringContainsString('Петро (12,500 × 150,00)', $files['services.csv']);
        $this->assertStringContainsString('1234,50', $files['payments.csv']);
        $this->assertStringContainsString('Петро', $files['employees.csv']);

        $data = json_decode($files['data.json'], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $data['format_version']);
        $this->assertSame('budmaister', $data['workspace']['slug']);
        $this->assertSame('Квартира на Липовій', $data['objects'][0]['name']);
        $this->assertArrayNotHasKey('public_token', $data['objects'][0]);
        $this->assertSame('Штукатурка', $data['objects'][0]['services'][0]['name']);
        $this->assertSame($employee->id, $data['objects'][0]['services'][0]['workers'][0]['employee_id']);
        $this->assertSame('Аванс', $data['objects'][0]['payments'][0]['name']);
        $this->assertSame($client->id, $data['clients'][0]['id']);
    }

    public function test_deleted_records_and_other_workspaces_are_not_exported(): void
    {
        ConstructionObject::factory()->ofWorkspace($this->workspace)->create(['name' => 'Видалений'])->delete();
        ConstructionObject::factory()->create(['name' => 'Чужий']);

        $files = $this->unzip($this->download()->assertOk());

        $this->assertStringNotContainsString('Видалений', $files['objects.csv']);
        $this->assertStringNotContainsString('Чужий', $files['objects.csv']);
    }

    public function test_a_personal_workspace_archive_has_no_team_file(): void
    {
        $personal = Workspace::factory()->personal()->ownedBy($this->user)->create(['slug' => 'personal-one']);

        $response = $this->actingAs($this->user, 'sanctum')->get('/api/v1/workspaces/personal-one/export')->assertOk();

        $this->assertArrayNotHasKey('employees.csv', $this->unzip($response));
    }

    public function test_formulas_are_neutralised_in_csv(): void
    {
        Client::factory()->ofWorkspace($this->workspace)->create(['name' => '=HYPERLINK("http://evil")']);

        $files = $this->unzip($this->download()->assertOk());

        $this->assertStringContainsString("'=HYPERLINK", $files['clients.csv']);
    }

    public function test_an_outsider_cannot_export(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/workspaces/budmaister/export')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/workspaces/budmaister/export/summary')
            ->assertForbidden();
    }

    public function test_a_guest_cannot_export(): void
    {
        $this->getJson('/api/v1/workspaces/budmaister/export')->assertUnauthorized();
    }

    public function test_exports_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->download()->assertOk();
        }

        $this->download()->assertStatus(429);
    }

    private function download(): TestResponse
    {
        return $this->actingAs($this->user, 'sanctum')->get('/api/v1/workspaces/budmaister/export', ['Accept' => 'application/json']);
    }

    /**
     * @return array<string, string>
     */
    private function unzip(TestResponse $response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'export-test-');
        copy($response->baseResponse->getFile()->getPathname(), $path);

        $zip = new ZipArchive;
        $zip->open($path);

        $files = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            $files[$name] = (string) $zip->getFromIndex($index);
        }

        $zip->close();
        unlink($path);

        return $files;
    }
}
