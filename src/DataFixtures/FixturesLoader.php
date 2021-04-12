<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class FixturesLoader
 *
 * @codeCoverageIgnore
 *
 * Load Faker fixtures thanks to Doctrine bundle.
 */
class FixturesLoader implements FixtureInterface
{
    /**
     * @var Faker\Generator
     */
    private Faker\Generator $faker;

    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $userPasswordEncoder;

    /**
     * LoadFixtures constructor.
     *
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        // Configure Faker to create french data
        $this->faker = Faker\Factory::create('fr_FR');
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * Load User and Task entities fixtures and save data.
     *
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        // Keep the same set of Faker data for each fixtures loading (on this computer)
        $this->faker->seed(2021); // Define what you want
        // Create User instances
        $this->createUsers($manager);
        // Create Task instances
        $this->createTasks($manager);
        // Save data
        $manager->flush();
    }

    /**
     * Create a fake starting set of tasks without author (and obviously without last editor).
     *
     * @param ObjectManager $manager
     *
     * @return void
     *
     * @throws \Exception
     */
    private function createTasks(ObjectManager $manager): void
    {
        $tasks = [];
        for ($i = 0; $i < 20; $i++) {
            $tasks[$i] = new Task();
            // Set task properties
            $tasks[$i]
                ->setTitle('Task ' . ($i + 1) . ': ' . $this->faker->word)
                ->setContent($this->faker->text)
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $this->faker->dateTimeBetween('-30 days', 'now', 'Europe/Paris')
                    )
                )
                ->setUpdatedAt($tasks[$i]->getCreatedAt());
            // Set task with "even" iteration "isDone" property to true
            0 !== $i % 2 ?: $tasks[$i]->toggle();
            // Persist data
            $manager->persist($tasks[$i]);
        }
    }

    /**
     * Create a fake starting set of users.
     *
     * @param ObjectManager $manager
     *
     * @return void
     *
     * @throws \Exception
     */
    private function createUsers(ObjectManager $manager): void
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[$i] = new User();
            // Set user properties
            $userName = strtolower($this->faker->firstName . '.' . $this->faker->lastName);
            $users[$i]
                ->setUserName($userName . '_' . ($i + 1) )
                ->setPassword(
                    // Use expected and validated format to encode
                    $this->userPasswordEncoder->encodePassword($users[$i], 'password' . '_' . ($i + 1) . 'A$')
                )
                ->setEmail($userName . '@' . $this->faker->freeEmailDomain)
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $this->faker->dateTimeBetween('-30 days', 'now', 'Europe/Paris')
                    )
                )
                ->setUpdatedAt($users[$i]->getCreatedAt());
            // Define first user as "admin" (A default role as "ROLE_USER" is defined by default in constructor!)
            if (0 === $i) {
                $users[$i]->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            }
            // Persist data
            $manager->persist($users[$i]);
        }
    }
}