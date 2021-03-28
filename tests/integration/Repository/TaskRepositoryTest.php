<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class TaskRepositoryTest
 *
 * Manage integration tests for task repository (entity data layer).
 */
class TaskRepositoryTest extends KernelTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static ?KernelInterface $kernel = null;

    /**
     * @var TaskRepository|null
     */
    private ?TaskRepository $taskRepository;

    /**
     * Setup needed instance(s).
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$kernel = static::bootKernel();
        // Access task repository private service using "static::$container"
        $this->taskRepository = static::$container->get(TaskRepository::class);
    }

    /**
     * Provide data to check task repository "findList" successful behavior filtering status.
     *
     * @return \Generator
     */
    public function provideCorrectListStatusData(): \Generator
    {
        yield [
            'Uses "done" for task list status' => [
                'status'      => 'done',
                'resultCount' => 10,
                'isDone'      => true
            ]
        ];
        yield [
            'Uses "undone" for task list status' => [
                'status'      => 'undone',
                'resultCount' => 10,
                'isDone'      => false
            ]
        ];
        yield [
            'Uses "null" for task list status' => [
                'status'      => null,
                'resultCount' => 20,
                'isDone'      => [true, false]
            ]
        ];
    }

    /**
     * Check that task list cannot be found with unexpected list status.
     *
     * @return void
     */
    public function testTaskRepositoryCannotFindListWithWrongStatus(): void
    {
        static::expectException(\InvalidArgumentException::class);
        $this->taskRepository->findList('unexpected');
    }

    /**
     * Check that task corresponding list can be found with correct list status.
     *
     * @dataProvider provideCorrectListStatusData
     *
     * @param array $data
     *
     * @return void
     */
    public function testTaskRepositoryCanFindCorrespondingListWitCorrectDoneStatus(array $data): void
    {
        $tasksData = $this->taskRepository->findList($data['status']);
        // "resultCount" tasks are undone/done in test database!
        static::assertCount($data['resultCount'], $tasksData);
        // Get "isDone" values among results to check it is the expected list depending on filter.
        for ($i = 0; $i < $data['resultCount']; $i++) {
            $expected = \boolval($tasksData[$i]['isDone']);
            if (!\is_array($data['isDone'])) {
                static::assertSame($data['isDone'], $expected);
            } else {
                static::assertTrue(\in_array($expected, $data['isDone']));
            }
        }
    }

    /**
     * Check that task list scalar data structure is same as expected.
     *
     * @return void
     */
    public function testTaskListScalarDataStructureIsSameAsExpected(): void
    {
        $tasksData = $this->taskRepository->findList();
        // Check data structure
        static::assertArrayHasKey('id', $tasksData[0]);
        static::assertArrayHasKey('title', $tasksData[0]);
        static::assertArrayHasKey('content', $tasksData[0]);
        static::assertArrayHasKey('isDone', $tasksData[0]);
        static::assertArrayHasKey('createdAt', $tasksData[0]);
        static::assertArrayHasKey('updatedAt', $tasksData[0]);
        static::assertArrayHasKey('author', $tasksData[0]);
        static::assertArrayHasKey('lastEditor', $tasksData[0]);
        // Check that task data includes dates already formatted thanks to MySQL
        $correspondingTaskInstance = $this->taskRepository->find(1);
        static::assertSame(
            $correspondingTaskInstance->getCreatedAt()->format('d/m/Y'),
            $tasksData[0]['createdAt']
        );
        static::assertSame(
            $correspondingTaskInstance->getUpdatedAt()->format('d/m/Y'),
            $tasksData[0]['updatedAt']
        );
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        static::ensureKernelShutdown();
        static::$kernel = null;
        $this->taskRepository = null;
        parent::tearDown();
    }
}