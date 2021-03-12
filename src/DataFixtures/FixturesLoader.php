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
                ->setUpdatedAt($tasks[$i]->getCreatedAt())
                ->toggle((bool) rand(0, 1));
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
     */
    private function createUsers(ObjectManager $manager): void
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[$i] = new User();
            // Set user properties
            $userName = $this->faker->userName;
            $users[$i]
                ->setUserName($userName . '_' . ($i + 1) )
                ->setPassword(
                    $this->userPasswordEncoder->encodePassword($users[$i], 'pass' . '_' . ($i + 1))
                )
                ->setEmail($userName. '@' . $this->faker->freeEmailDomain);
            // Persist data
            $manager->persist($users[$i]);
        }
    }
}