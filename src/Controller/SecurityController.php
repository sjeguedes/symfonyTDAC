<?php

namespace App\Controller;

use App\Form\Type\LoginUserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 */
class SecurityController extends AbstractController
{
    /**
     * log in a user.
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     *
     * @Route("/login", name="login", methods={"GET"})
     */
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
         return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            // Create a Symfony form for authentication process
            'loginUser'     => $this->createForm(LoginUserType::class)->createView()
        ]);
    }

    /**
     * Create a route for guard authentication process.
     *
     * @codeCoverageIgnore
     *
     * @return void
     *
     * @Route("/login_check", name="login_check", methods={"GET", "POST"})
     *
     * @throws \Exception
     */
    public function loginCheck(): void
    {
        // This code is never reached.
        throw new \Exception('This code should never be executed!');
    }

    /**
     * Create a route to log out a user.
     *
     * @codeCoverageIgnore
     *
     * @return void
     *
     * @Route("/logout", name="logout", methods={"GET"})
     *
     * @throws \Exception
     */
    public function logoutCheck(): void
    {
        // This code is never reached.
        throw new \Exception('This code should never be executed!');
    }
}
