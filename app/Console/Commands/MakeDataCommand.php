<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:data', description: 'Create a new data transfer object')]
final class MakeDataCommand extends GeneratorCommand
{
    protected $name = 'make:data';

    protected $description = 'Create a new data transfer object';

    protected $type = 'Data';

    protected function getStub(): string
    {
        return $this->laravel->basePath('stubs/data.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\DataTransferObjects';
    }
}
