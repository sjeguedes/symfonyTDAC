<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Factory;

use App\Entity\Factory\ModelFactory;
use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Class ModelFactoryTest
 *
 * Manage unit tests for model factory.
 */
class ModelFactoryTest extends TestCase
{
    /**
     * @var ModelFactory|null
     */
    private ?ModelFactory $modelFactory;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->modelFactory = new ModelFactory();
    }

    /**
     * Provide a set of entity references to check factory successful behavior.
     *
     * @return \Generator
     */
    public function provideCorrectEntityReferences(): \Generator
    {
        yield [
            'Uses task key' => [
                'reference' => 'task',
                'className' => Task::class
            ]
        ];
        yield [
            'Uses task F.Q.C.N' => [
                'reference' => Task::class,
                'className' => Task::class
            ]
        ];
        yield [
            'Uses user key' => [
                'reference' => 'user',
                'className' => User::class
            ]
        ];
        yield [
            'Uses user F.Q.C.N' => [
                'reference' => User::class,
                'className' => User::class
            ]
        ];
    }

    /**
     * Check that entity instance cannot be created.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testModelCannotBeCreatedWithWrongReference(): void
    {
        static::expectException(\RuntimeException::class);
        $this->modelFactory->create('inexistant');
    }

    /**
     * Check that expected entity instance can be created.
     *
     * @dataProvider provideCorrectEntityReferences
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testModelCanBeCreatedWithCorrectReference(array $data): void
    {
        $entity = $this->modelFactory->create($data['reference']);
        static::assertInstanceOf($data['className'], $entity);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->modelFactory = null;
    }
}