<?php

/**
 * @file tests/jobs/submissions/UpdateSubmissionSearchJobTest.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for the submission search reindexing job.
 */

namespace PKP\tests\jobs\submissions;

use PKP\tests\PKPTestCase;
use PKP\jobs\submissions\UpdateSubmissionSearchJob;
use Mockery;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class UpdateSubmissionSearchJobTest extends PKPTestCase
{
    /**
     * base64_encoded serializion from OJS 3.4.0
     */
    protected string $serializedJobData = <<<END
    O:46:"PKP\\jobs\\submissions\\UpdateSubmissionSearchJob":3:{s:15:"\0*\0submissionId";i:17;s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}
    END;

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperJobInstance(): void
    {
        $this->assertInstanceOf(
            UpdateSubmissionSearchJob::class,
            unserialize($this->serializedJobData)
        );
    }
    
    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        /** @var UpdateSubmissionSearchJob $updateSubmissionSearchJob */
        $updateSubmissionSearchJob = unserialize($this->serializedJobData);

        // Mock the Submission facade to return a fake submission when Repo::submission()->get($id) is called
        $mock = Mockery::mock(app(\APP\submission\Repository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(new \APP\submission\Submission())
            ->getMock();

        app()->instance(\APP\submission\Repository::class, $mock);

        // Test that the job can be handled without causing an exception.
        $this->assertNull($updateSubmissionSearchJob->handle());
    }
}

