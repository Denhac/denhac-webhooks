<?php

namespace App\Console\Commands;

use App\Issues\Types\IssueBase;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use ReflectionClass;

class MakeIssue extends GeneratorCommand
{
    private const NamespaceOffset = [
        'AccessCards' => 0,
        'GoogleGroups' => 100,
        'InternalConsistency' => 200,
        'Slack' => 300,
        'GitHub' => 400,
        'Stripe' => 500,
        'WordPress' => 600,
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:issue {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new issue class';

    protected $type = 'Issue';

    protected function getStub()
    {
        return app_path('Console/Stubs/make-issue.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'/Issues/Types';
    }

    protected function makeDirectory($path): string
    {
        $nameSpaceType = $this->getNameSpaceType();

        if (empty($nameSpaceType)) {
            $namespaceOptionString = implode(', ', array_keys(self::NamespaceOffset));
            throw new \Exception("No namespace given, please use one of: $namespaceOptionString");
        }

        if (! array_key_exists($nameSpaceType, self::NamespaceOffset)) {
            throw new \Exception("Unknown namespace type \"$nameSpaceType\". Please add new offsets into NamespaceOffset in App\Console\Commands\MakeIssue");
        }

        parent::makeDirectory($path);

        return $path;
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        $issuesNumbers = collect(get_declared_classes())
            ->filter(fn ($n) => str_starts_with($n, $this->getNamespace($name)))
            ->map(fn ($n) => new ReflectionClass($n))
            ->filter(fn ($reflect) => $reflect->isSubclassOf(IssueBase::class))
            ->map(fn ($reflect) => $reflect->getName())
            ->map(fn ($n) => $n::getIssueNumber())
            ->values();

        $nameSpaceType = $this->getNameSpaceType();
        if ($issuesNumbers->isEmpty()) {
            $nextIssueNumber = self::NamespaceOffset[$nameSpaceType];
        } else {
            $nextIssueNumber = $issuesNumbers->max() + 1;
        }

        $stub = str_replace(
            'DummyIssueNumber',
            $nextIssueNumber,
            $stub
        );

        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $newIssueTitle = Str::ucfirst(Str::snake($class, ' '));
        $nameSpaceTypeTitle = Str::headline($nameSpaceType);

        $issueTitle = "$nameSpaceTypeTitle: $newIssueTitle";
        $issueTitle = str_replace(['git hub', 'Git Hub', 'Git hub'], 'GitHub', $issueTitle); // Special handling to avoid "Git Hub"

        return str_replace(
            'DummyIssueTitle',
            $issueTitle,
            $stub
        );
    }

    protected function getNameSpaceType(): string
    {
        $rootNamespace = $this->rootNamespace();
        $defaultNamespace = $this->getDefaultNamespace(trim($rootNamespace, '\\'));
        $defaultNamespace = str_replace('/', '\\', $defaultNamespace);
        $namespace = $this->getNamespace($this->qualifyClass($this->getNameInput()));

        return ltrim(str_replace($defaultNamespace, '', $namespace), '\\');
    }
}
