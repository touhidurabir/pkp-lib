<?php

namespace PKP\tests\jobs\doi;

use Mockery;
use PKP\db\DAORegistry;
use PKP\context\Context;
use APP\core\Application;
use PKP\tests\PKPTestCase;
use PKP\jobs\doi\DepositContext;
use APP\doi\Repository as DoiRepository;

/**
 * @runTestsInSeparateProcesses
 *
 * @see https://docs.phpunit.de/en/9.6/annotations.html#runtestsinseparateprocesses
 */
class DepositContextTest extends PKPTestCase
{
    /**
     * Serializion from OJS 3.4.0
     */
    protected string $serializedJobData = 'O:27:"PKP\jobs\doi\DepositContext":3:{s:12:" * contextId";i:1;s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}';

    /**
     * Test job is a proper instance
     */
    public function testUnserializationGetProperDepositIssueJobInstance(): void
    {
        $this->assertInstanceOf(DepositContext::class, unserialize($this->serializedJobData));
    }

    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        $depositContextJob = unserialize($this->serializedJobData);

        $contextDaoClass = get_class(Application::getContextDAO());

        $contextMock = Mockery::mock(get_class(Application::getContextDAO()->newDataObject()))
            ->makePartial()
            ->shouldReceive('getData')
            ->with(Context::SETTING_DOI_AUTOMATIC_DEPOSIT)
            ->andReturn(true)
            ->getMock();

        $contextDaoMock = Mockery::mock($contextDaoClass)
            ->makePartial()
            ->shouldReceive('getId')
            ->withAnyArgs()
            ->andReturn($contextMock)
            ->getMock();

        DAORegistry::registerDAO(substr(strrchr($contextDaoClass, '\\'), 1), $contextDaoMock);

        $doiRepoMock = Mockery::mock(app(DoiRepository::class))
            ->makePartial()
            ->shouldReceive('depositAll')
            ->with($contextMock)
            ->andReturn(null)
            ->getMock();

        app()->instance(DoiRepository::class, $doiRepoMock);

        $this->assertNull($depositContextJob->handle());
    }
}
