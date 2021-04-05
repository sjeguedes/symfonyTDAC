<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type\Helpers;

use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AbstractFormTypeKernelTestCase
 *
 * Manage form type integration tests common actions with a real validation.
 *
 * IMPORTANT: please note that unit tests are not used due to unique entity validation constraint!
 * @see use this link for form type unit testing: https://symfony.com/doc/current/form/unit_testing.html
 */
abstract class AbstractFormTypeKernelTestCase extends KernelTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static ?KernelInterface $kernel = null;

    /**
     * @var FormFactoryInterface|null
     */
    protected ?FormFactoryInterface $formFactory = null;

    /**
     * @var ObjectManager|null
     */
    protected ?ObjectManager $entityManager = null;

    /**
     * @var ValidatorInterface|null
     */
    protected ?ValidatorInterface $validator;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$kernel = static::bootKernel();
        // Use a container builder and container constraint validator factory to validate entity uniqueness
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = static::$kernel->getContainer()->get('doctrine');
        $container = new ContainerBuilder();
        // Set UniqueEntityValidator service in special container.
        $container->set('doctrine.orm.validator.unique', new UniqueEntityValidator($managerRegistry));
        // Get Validator with unique entity constraint and other ones
        $this->validator = Validation::createValidatorBuilder()
            // Feed UniqueEntityValidator instance thanks to factory
            ->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($container))
            // Get all entity constraint declared in annotations
            ->enableAnnotationMapping()
            ->getValidator();
        // Configure and create form factory
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($this->validator))
            ->getFormFactory();
        // Feed entity manager
        $this->entityManager = $managerRegistry->getManager();
    }

    /**
     * Create a form with form factory service.
     *
     * @param string $formTypeClassName a F.Q.C.N
     * @param object $dataModel
     * @param array  $options
     *
     * @return FormInterface
     */
    protected function createForm(string $formTypeClassName, object $dataModel, array $options = []): FormInterface
    {
        $defaultOptions = [
            // Define common default options here if needed!
        ];
        $options = empty($options) ? $defaultOptions : array_merge($defaultOptions, $options);

        return $this->formFactory->create($formTypeClassName, $dataModel, $options);
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
        $this->validator = null;
        $this->entityManager->close();
        $this->entityManager = null;
        $this->formFactory = null;
        parent::tearDown();
    }
}