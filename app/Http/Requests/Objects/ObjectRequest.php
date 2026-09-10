<?php

declare(strict_types=1);

namespace App\Http\Requests\Objects;

use App\DataTransferObjects\Objects\ObjectData;
use App\Enums\ObjectStatus;
use App\Http\Requests\ApiFormRequest;
use App\Models\ConstructionObject;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

abstract class ObjectRequest extends ApiFormRequest
{
    /** @var array<int, string> */
    protected const DAYS = ['started_at', 'finished_at', 'actual_started_at', 'actual_finished_at'];

    protected function workspace(): Workspace
    {
        $workspace = $this->route('workspace');

        abort_unless($workspace instanceof Workspace, 404);

        return $workspace;
    }

    protected function object(): ?ConstructionObject
    {
        $object = $this->route('object');

        return $object instanceof ConstructionObject ? $object : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'client_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query): void {
                    $query->where('workspace_id', $this->workspace()->getKey())->whereNull('deleted_at');
                }),
            ],
            'status' => ['sometimes', Rule::enum(ObjectStatus::class)],
            'started_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'finished_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'actual_started_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'actual_finished_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'archived' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'address', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->string($field)->value())]);
            }
        }

        foreach (self::DAYS as $field) {
            if (is_string($this->input($field)) && trim($this->string($field)->value()) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny([...self::DAYS, 'status'])) {
                return;
            }

            $this->checkDates($validator);
            $this->checkStatus($validator);
        });
    }

    private function checkDates(Validator $validator): void
    {
        $planStart = $this->day('started_at');
        $planFinish = $this->day('finished_at');

        if ($planStart !== null && $planFinish !== null && $planFinish < $planStart) {
            $validator->errors()->add('finished_at', __('messages.objects.finish_before_start'));
        }

        $start = $this->day('actual_started_at');
        $finish = $this->day('actual_finished_at');

        if ($start !== null && $finish !== null && $finish < $start) {
            $validator->errors()->add('actual_finished_at', __('messages.objects.finish_before_start'));
        }

        if ($finish !== null && $start === null) {
            $validator->errors()->add('actual_started_at', __('messages.objects.actual_start_first'));
        }
    }

    private function checkStatus(Validator $validator): void
    {
        $status = $this->status();

        if ($status->needsActualStart() && $this->day('actual_started_at') === null) {
            $validator->errors()->add('actual_started_at', __('messages.objects.actual_start_required'));
        }

        if ($status->isDone() && $this->day('actual_finished_at') === null) {
            $validator->errors()->add('actual_finished_at', __('messages.objects.actual_finish_required'));
        }
    }

    private function day(string $field): ?string
    {
        if ($this->has($field)) {
            $value = $this->input($field);

            return is_string($value) && $value !== '' ? $value : null;
        }

        return $this->object()?->{$field}?->format('Y-m-d');
    }

    private function status(): ObjectStatus
    {
        $value = $this->input('status');

        if (is_string($value)) {
            return ObjectStatus::tryFrom($value) ?? ObjectStatus::default();
        }

        return $this->object()?->status ?? ObjectStatus::default();
    }

    public function toData(): ObjectData
    {
        return ObjectData::fromArray($this->validated());
    }
}
