<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeIssue extends GeneratorCommand
{
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

    protected $type = "Issue";

    protected function getStub()
    {
        return app_path('Console/Stubs/make-issue.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Issues\Types';
    }
}
