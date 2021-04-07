<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler\Helpers;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class AbstractFormHandlerTestCase
 *
 * Manage all form handlers unit tests common actions as helper.
 */
abstract class AbstractFormHandlerTestCase extends TestCase
{
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
    }

    /**
     * Build a real form factory instance which manages request handler and data validation.
     *
     * @param Request $request
     * @param string  $modelClassName a F.Q.C.N which corresponds to an entity class
     *
     * @return FormFactoryInterface
     */
    protected function buildFormFactory(Request $request, string $modelClassName): FormFactoryInterface
    {
        // Create the expected form factory builder
        $formFactoryBuilder = $this->createFormFactoryBuilder($request, $modelClassName);

        return $formFactoryBuilder->getFormFactory();
    }

    /**
     * Create a form factory builder with necessary configuration.
     *
     * @param Request $request
     * @param string  $modelClassName
     *
     * @return FormFactoryBuilderInterface
     */
    protected function createFormFactoryBuilder(Request $request, string $modelClassName): FormFactoryBuilderInterface
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $serverParams = new ServerParams($requestStack);
        $requestHandler = new HttpFoundationRequestHandler($serverParams);
        // Get an annotation loader to configure a special validator
        // without unique entity constraint which depends on database.
        $metadataLoader = $this->getMetadataAnnotationLoader();
        $metadataLoader->loadClassMetadata(new ClassMetadata($modelClassName));
        // Configure a validator with a builder
        $validatorBuilder = Validation::createValidatorBuilder();
        $validator = $validatorBuilder
            ->addLoader($metadataLoader)
            ->getValidator();
        $formFactoryBuilder = Forms::createFormFactoryBuilder();
        // Configure a form factory with this particular validator and a request handler
        $formFactoryBuilder->addTypeExtension(
            new FormTypeHttpFoundationExtension($requestHandler)
        );
        $formFactoryBuilder->addExtension(
            new ValidatorExtension($validator)
        );

        return $formFactoryBuilder;
    }

    /**
     * Get a annotation loader to configure a custom validator
     * without unique entity constraint for unit tests.
     *
     * @return LoaderInterface
     *
     * @see https://symfony.com/doc/current/components/validator/resources.html#the-annotationloader
     */
    private function getMetadataAnnotationLoader(): LoaderInterface
    {
        return new class() implements LoaderInterface {
            /**
             * Use this implementation to get entity constraints properties only.
             *
             * {@inheritdoc}
             */
            public function loadClassMetadata(ClassMetadata $metadata): bool
            {
                $reader = new AnnotationReader();
                $properties = (new \ReflectionClass($metadata->getClassName()))->getProperties();
                foreach ($properties as $property) {
                    if (!empty($annotations = $reader->getPropertyAnnotations($property))) {
                        array_filter(
                            $annotations, function ($value) use ($metadata, $property): bool {
                            if ($value instanceof Constraint) {
                                $metadata->addPropertyConstraint($property->name, $value);
                                return true;
                            }
                            return false;
                        });
                    }
                }

                return true;
            }
        };
    }

    /**
     * Return a mocked authenticated user.
     *
     * @param MockObject $tokenStorage
     *
     * @return MockObject
     */
    protected function getMockedUserWithExpectations(MockObject $tokenStorage): MockObject
    {
        $token = static::createMock(TokenInterface::class);
        $authenticatedUser = static::createMock(UserInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($authenticatedUser);

        return $authenticatedUser;
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }
}