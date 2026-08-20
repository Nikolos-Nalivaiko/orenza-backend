<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generates the contract and its Eloquent implementation in one go, and
 * reminds the developer to bind them in the RepositoryServiceProvider.
 */
#[AsCommand(name: 'make:repository', description: 'Create a repository contract and its Eloquent implementation')]
final class MakeRepositoryCommand extends GeneratorCommand
{
    protected $name = 'make:repository';

    protected $description = 'Create a repository contract and its Eloquent implementation';

    protected $type = 'Repository';

    public function handle(): ?bool
    {
        $this->writeInterface();

        $result = parent::handle();

        if ($result !== false) {
            $this->components->info(sprintf(
                'Bind %s to %s in App\Providers\RepositoryServiceProvider.',
                $this->interfaceName(),
                $this->repositoryName(),
            ));
        }

        return $result;
    }

    protected function getStub(): string
    {
        return $this->laravel->basePath('stubs/repository.stub');
    }

    /**
     * Always generate a class suffixed with "Repository".
     */
    protected function getNameInput(): string
    {
        return $this->repositoryName();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Repositories\Eloquent';
    }

    /**
     * @param  string  $name
     */
    protected function buildClass($name): string
    {
        return $this->replacePlaceholders(parent::buildClass($name));
    }

    private function writeInterface(): void
    {
        $path = $this->laravel->basePath('app/Repositories/Contracts/'.$this->interfaceName().'.php');

        if ($this->files->exists($path)) {
            $this->components->warn($this->interfaceName().' already exists.');

            return;
        }

        $this->makeDirectory($path);

        $stub = $this->files->get($this->laravel->basePath('stubs/repository.interface.stub'));

        $this->files->put($path, $this->replacePlaceholders($stub));

        $this->components->info($this->interfaceName().' created successfully.');
    }

    private function replacePlaceholders(string $stub): string
    {
        return str_replace(
            ['{{ interface }}', '{{ modelNamespace }}', '{{ model }}'],
            [$this->interfaceName(), $this->modelClass(), class_basename($this->modelClass())],
            $stub,
        );
    }

    private function repositoryName(): string
    {
        return Str::finish(Str::studly((string) $this->argument('name')), 'Repository');
    }

    private function interfaceName(): string
    {
        return $this->repositoryName().'Interface';
    }

    private function modelClass(): string
    {
        $model = (string) ($this->option('model') ?: Str::before($this->repositoryName(), 'Repository'));

        return str_starts_with($model, $this->rootNamespace())
            ? $model
            : $this->rootNamespace().'Models\\'.Str::studly($model);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model handled by the repository'],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the repository if it already exists'],
        ];
    }
}
