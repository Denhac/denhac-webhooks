<?php

namespace Tests\Unit;


use App\Issues\Types\IssueBase;
use ReflectionClass;
use Tests\TestCase;

class PreventDuplicateIssueNumbersTest extends TestCase
{
    /** @test */
    public function preventDuplicateIssueNumbers()
    {
        $reflection = new ReflectionClass(IssueBase::class);

        $flipped = collect();  // issue number is the key for this one.
        foreach($reflection->getConstants() as $constant => $issueNumber) {
            if($flipped->has($issueNumber)) {
                $existingIssueConstant = $flipped->get($issueNumber);
                self::fail("$existingIssueConstant and $constant both share issue number $issueNumber");
            }
            $flipped->put($issueNumber, $constant);
        }

        self::assertTrue(true);  // Avoid "test did not perform any assertions" issue.
    }
}
