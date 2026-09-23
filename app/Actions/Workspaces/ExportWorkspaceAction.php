<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Contracts\Action;
use App\Models\Client;
use App\Models\ConstructionObject;
use App\Models\Employee;
use App\Models\Material;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceWorker;
use App\Models\Workspace;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Support\Export\CsvWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RuntimeException;
use ZipArchive;

final readonly class ExportWorkspaceAction implements Action
{
    public const int FORMAT_VERSION = 1;

    public function __construct(
        private ObjectRepositoryInterface $objects,
        private ClientRepositoryInterface $clients,
        private EmployeeRepositoryInterface $employees,
    ) {}

    public function handle(Workspace $workspace): string
    {
        $objects = $this->objects->listForExport($workspace);
        $clients = $this->clients->query()->ofWorkspace($workspace)->orderBy('id')->get();
        $employees = $workspace->type->hasTeam()
            ? $this->employees->query()->ofWorkspace($workspace)->orderBy('id')->get()
            : null;

        $files = [
            'objects.csv' => $this->objectsCsv($objects),
            'clients.csv' => $this->clientsCsv($clients),
            'materials.csv' => $this->materialsCsv($objects),
            'services.csv' => $this->servicesCsv($objects),
            'payments.csv' => $this->paymentsCsv($objects),
        ];

        if ($employees !== null) {
            $files['employees.csv'] = $this->employeesCsv($employees);
        }

        $files['data.json'] = $this->json($workspace, $objects, $clients, $employees);

        return $this->zip($files);
    }

    /**
     * @param  array<string, string>  $files
     */
    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'orenza-export-');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the export archive.');
        }

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }

    /**
     * @param  Collection<int, ConstructionObject>  $objects
     */
    private function objectsCsv(Collection $objects): string
    {
        return CsvWriter::build(
            ['ID', 'Назва', 'Адреса', 'Замовник', 'Статус', 'Опис', 'Початок (план)', 'Завершення (план)', 'Початок (факт)', 'Завершення (факт)', 'Знижка, %', 'Знижка, сума', 'Сума для замовника', 'Оплачено', 'В архіві', 'Створено'],
            $objects->map(static fn (ConstructionObject $object): array => [
                $object->id,
                $object->name,
                $object->address,
                $object->client?->name,
                $object->status->label(),
                $object->description,
                $object->started_at,
                $object->finished_at,
                $object->actual_started_at,
                $object->actual_finished_at,
                CsvWriter::decimal($object->discount_percent),
                CsvWriter::decimal($object->discount_amount),
                CsvWriter::decimal(number_format($object->clientTotal(), 2, '.', '')),
                CsvWriter::decimal(number_format($object->paidTotal(), 2, '.', '')),
                $object->archived_at !== null,
                $object->created_at,
            ]),
        );
    }

    /**
     * @param  Collection<int, Client>  $clients
     */
    private function clientsCsv(Collection $clients): string
    {
        return CsvWriter::build(
            ['ID', 'Тип', 'Назва', 'Контактна особа', 'Телефон', 'Email', 'Знижка, %', 'Нотатки', 'Створено'],
            $clients->map(static fn (Client $client): array => [
                $client->id,
                $client->type->label(),
                $client->name,
                $client->contact,
                $client->phone,
                $client->email,
                CsvWriter::decimal($client->discount),
                $client->notes,
                $client->created_at,
            ]),
        );
    }

    /**
     * @param  Collection<int, ConstructionObject>  $objects
     */
    private function materialsCsv(Collection $objects): string
    {
        return CsvWriter::build(
            ['ID', 'ID обʼєкта', 'Обʼєкт', 'Назва', 'Одиниця', 'Кількість', 'Хто купує', 'Собівартість', 'Ціна для замовника', 'Статус', 'Погоджено замовником'],
            $objects->flatMap(static fn (ConstructionObject $object) => $object->materials->map(
                static fn (Material $material): array => [
                    $material->id,
                    $object->id,
                    $object->name,
                    $material->name,
                    $material->unit,
                    CsvWriter::decimal($material->quantity),
                    $material->buyer->label(),
                    CsvWriter::decimal($material->cost_price),
                    CsvWriter::decimal($material->client_price),
                    $material->status->label(),
                    $material->approved_by_client,
                ],
            )),
        );
    }

    /**
     * @param  Collection<int, ConstructionObject>  $objects
     */
    private function servicesCsv(Collection $objects): string
    {
        return CsvWriter::build(
            ['ID', 'ID обʼєкта', 'Обʼєкт', 'Назва', 'Опис', 'Одиниця', 'Обсяг (план)', 'Обсяг (факт)', 'Ціна для замовника', 'Статус', 'Виконавці'],
            $objects->flatMap(static fn (ConstructionObject $object) => $object->services->map(
                static fn (Service $service): array => [
                    $service->id,
                    $object->id,
                    $object->name,
                    $service->name,
                    $service->description,
                    $service->unit,
                    CsvWriter::decimal($service->planned_volume),
                    CsvWriter::decimal($service->actual_volume),
                    CsvWriter::decimal($service->client_price),
                    $service->status->label(),
                    $service->workers
                        ->map(static fn (ServiceWorker $worker): string => sprintf(
                            '%s (%s × %s)',
                            $worker->employee?->name ?? '—',
                            CsvWriter::decimal($worker->volume),
                            CsvWriter::decimal($worker->rate),
                        ))
                        ->implode(', '),
                ],
            )),
        );
    }

    /**
     * @param  Collection<int, ConstructionObject>  $objects
     */
    private function paymentsCsv(Collection $objects): string
    {
        return CsvWriter::build(
            ['ID', 'ID обʼєкта', 'Обʼєкт', 'Назва', 'Опис', 'Сума', 'Статус', 'Дата оплати', 'Видно замовнику'],
            $objects->flatMap(static fn (ConstructionObject $object) => $object->payments->map(
                static fn (Payment $payment): array => [
                    $payment->id,
                    $object->id,
                    $object->name,
                    $payment->name,
                    $payment->description,
                    CsvWriter::decimal($payment->amount),
                    $payment->status->label(),
                    $payment->paid_at,
                    $payment->client_visible,
                ],
            )),
        );
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    private function employeesCsv(Collection $employees): string
    {
        return CsvWriter::build(
            ['ID', 'Імʼя', 'Роль', 'Телефон', 'Email', 'Статус', 'Нотатки', 'Створено'],
            $employees->map(static fn (Employee $employee): array => [
                $employee->id,
                $employee->name,
                $employee->role,
                $employee->phone,
                $employee->email,
                $employee->status->label(),
                $employee->notes,
                $employee->created_at,
            ]),
        );
    }

    /**
     * @param  Collection<int, ConstructionObject>  $objects
     * @param  Collection<int, Client>  $clients
     * @param  Collection<int, Employee>|null  $employees
     */
    private function json(Workspace $workspace, Collection $objects, Collection $clients, ?Collection $employees): string
    {
        $data = [
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => now()->toIso8601String(),
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'type' => $workspace->type->value,
                'created_at' => $workspace->created_at?->toIso8601String(),
            ],
            'clients' => $clients->map(static fn (Client $client): array => self::attributes($client))->values()->all(),
            'employees' => $employees?->map(static fn (Employee $employee): array => self::attributes($employee))->values()->all(),
            'objects' => $objects->map(static fn (ConstructionObject $object): array => [
                ...self::attributes($object, ['cover', 'public_token']),
                'materials' => $object->materials->map(static fn (Material $material): array => self::attributes($material, ['construction_object_id']))->values()->all(),
                'services' => $object->services->map(static fn (Service $service): array => [
                    ...self::attributes($service, ['construction_object_id']),
                    'workers' => $service->workers->map(static fn (ServiceWorker $worker): array => self::attributes($worker, ['id', 'service_id']))->values()->all(),
                ])->values()->all(),
                'payments' => $object->payments->map(static fn (Payment $payment): array => self::attributes($payment, ['construction_object_id']))->values()->all(),
            ])->values()->all(),
        ];

        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<string>  $except
     * @return array<string, mixed>
     */
    private static function attributes(Model $model, array $except = []): array
    {
        return Arr::except($model->attributesToArray(), ['workspace_id', 'deleted_at', ...$except]);
    }
}
