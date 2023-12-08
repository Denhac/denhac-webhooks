<?php

namespace Tests\Unit;

use App\Issues\Types\IssueBase;
use ReflectionClass;
use Tests\TestCase;

class PreventDuplicateIssueNumbersTest extends TestCase
{
    /** @test */
    public function preventDuplicateIssueNumbers(): void
    {
        $issues = collect(get_declared_classes())
            ->filter(fn ($name) => str_starts_with($name, 'App\\Issues\\Types'))
            ->map(fn ($name) => new ReflectionClass($name))
            ->filter(fn ($reflect) => $reflect->isSubclassOf(IssueBase::class))
            ->map(fn ($reflect) => $reflect->getName());

        $issueNumbers = collect();

        foreach ($issues as $issue) {
            /** @var IssueBase $issue */
            $issueNumber = $issue::getIssueNumber();
            if ($issueNumbers->has($issueNumber)) {
                $existingClass = $issueNumbers->get($issueNumber);
                $newClass = $issue;
                self::fail("$existingClass and $newClass both share issue number $issueNumber");
            }
            $issueNumbers->put($issueNumber, $issue);
        }

        self::assertTrue(true);  // Avoid "test did not perform any assertions" issue.
    }
}
