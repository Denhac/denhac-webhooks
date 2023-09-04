<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeIssueChecker extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:issue-checker {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new issue checker class';

    protected $type = 'Issue Checker';

    protected function getStub()
    {
        return app_path('Console/Stubs/make-issue-checker.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Issues\Checkers';
    }
}
