<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler\Helpers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class AbstractFormHandlerTestCase
 *
 * Manage all form handlers tests common actions as helper.
 */
abstract class AbstractFormHandlerTestCase extends TestCase
{
    /**
     * Build a real form factory instance which manages request handler and data validation.
     *
     * @param Request $request
     *
     * @return FormFactoryInterface
     */
    protected function buildFormFactory(Request $request): FormFactoryInterface
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $serverParams = new ServerParams($requestStack);
        $requestHandler = new HttpFoundationRequestHandler($serverParams);
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
        // Return a form factory with correct configuration (request handling and data validation)
        return Forms::createFormFactoryBuilder()
            ->addTypeExtension(
                new FormTypeHttpFoundationExtension($requestHandler)
            )
            ->addExtension(
                new ValidatorExtension($validator)
            )
            ->getFormFactory();
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
}