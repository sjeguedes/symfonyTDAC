<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\Type\LoginUserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 *
 * Manage application login / logout process.
 */
class SecurityController extends AbstractController
{
    /**
     * Log in a user.
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     *
     * @Route("/login", name="login", methods={"GET", "POST"})
     */
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect to homepage if current user is already authenticated (same behaviour as just logged in user)!
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            // Create a Symfony form for authentication process
            'login_user'    => $this->createForm(LoginUserType::class)->createView()
        ]);
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
