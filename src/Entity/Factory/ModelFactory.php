<?php

declare(strict_types=1);

namespace App\Entity\Factory;

use App\Entity\Task;
use App\Entity\User;

/**
 * Class ModelFactory
 *
 * Manage entity production.
 */
class ModelFactory implements ModelFactoryInterface
{
    /**
     * Define expected entity types to produce.
     */
    private const MODEL_CLASS_NAMES = [
        'task' => Task::class,
        'user' => User::class
    ];

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function create(string $modelReference): object
    {
        $isKey = isset(self::MODEL_CLASS_NAMES[$modelReference]);
        $isValue = \in_array($modelReference, self::MODEL_CLASS_NAMES);
        if (!$isKey && !$isValue) {
            throw new \RuntimeException('Model to create is unknown!');
        }
        $className = $isKey ? self::MODEL_CLASS_NAMES[$modelReference] : $modelReference;

        return new $className();
    }
}