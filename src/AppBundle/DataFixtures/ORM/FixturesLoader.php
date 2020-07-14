<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Task;
use AppBundle\Entity\User;
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
    private $faker;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    /**
     * LoadFixtures constructor.
     *
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        // Configure Faker to create french data
        $this->faker = $faker = Faker\Factory::create('fr_FR');
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * Load User and Task entities fixtures and save data.
     *
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // Keep the same set of Faker data for each fixtures loading (on this computer)
        $this->faker->seed(2020); // Define what you want

        // Create User instances
        $users = [];
        for ($i = 0; $i < 5; $i ++) {
            $users[$i] = new User();
            // Sadly, setters are not chained in the forked project!
            $userName = $this->faker->userName;
            $users[$i]->setUserName($userName . '_' . ($i + 1) );
            $users[$i]->setPassword(
                $this->userPasswordEncoder->encodePassword($users[$i], 'pass' . '_' . ($i + 1))
            );
            $users[$i]->setEmail($userName. '@' . $this->faker->freeEmailDomain);
            $manager->persist($users[$i]);
        }

        // Create Task instances
        $tasks = [];
        for ($i = 0; $i < 20; $i ++) {
            $tasks[$i] = new Task();
            // Sadly, setters are not chained in the base project!
            $tasks[$i]->setTitle('Task ' . ($i + 1) . ': ' . $this->faker->word);
            $tasks[$i]->setContent($this->faker->text);
            $tasks[$i]->setCreatedAt(
                $this->faker->dateTimeBetween('-30 days', 'now', 'Europe/Paris')
            );
            $tasks[$i]->toggle(array_rand([true, false]));
            $manager->persist($tasks[$i]);
        }

        // Save data
        $manager->flush();
    }
}