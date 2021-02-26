<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * Class TaskTest
 *
 * Manage unit tests for Task entity.
 */
class TaskTest extends TestCase
{
    /**
     * @var Task|null
     */
    private ?Task $task;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->task = new Task();
    }

    /**
     * Provide a set of entity data to check validation rules.
     *
     * @return \Generator
     */
    public function provideDataToValidate(): \Generator
    {
        yield [
            'Succeeds when data is correct' => [
                'title'   => 'Nouvelle tâche',
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isValid' =>  true
            ]
        ];
        yield [
            'Fails when title is missing' => [
                'title'   => '',
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isValid' =>  false
            ]
        ];
        yield [
            'Fails when content is missing' => [
                'title'   => 'Nouvelle tâche',
                'content' => '',
                'isValid' =>  false
            ]
        ];
    }

    /**
     * Check that a new task should not be marked as done by default with constructor.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testNewTaskShouldNotBeMarkedAsDone(): void
    {
        static::assertTrue(false === $this->task->isDone());
    }

    /**
     * Test if update date is before creation date and throws an exception.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskUpdateDateIsInitiallyEqualToCreation(): void
    {
        static::expectException(\RuntimeException::class);
        $this->task->setUpdatedAt(new \DateTimeImmutable('-1day'));
        $this->assertEquals($this->task->getCreatedAt(), $this->task->getUpdatedAt());
    }

    /**
     * Check that entity validation rules are correctly set.
     *
     * @dataProvider provideDataToValidate
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testValidationRulesAreCorrectlySet(array $data): void
    {
        // Define an instance to validate
        $entity = $this->task
            ->setTitle($data['title'])
            ->setContent($data['content']);
        // Get validation rules from annotations
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
        // Validate instance
        $errors = $validator->validate($entity);
        // Check correct validation result for each case
        $this->assertEquals($data['isValid'], count($errors) == 0);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->task = null;
    }
}