<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'employee_id', 'volume', 'rate'])]
class ServiceWorker extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume' => 'decimal:3',
            'rate' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function cost(): float
    {
        return (float) $this->volume * (float) $this->rate;
    }
}
