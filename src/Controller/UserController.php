<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Type\Base\BaseUserType;
use App\View\Builder\ViewModelBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserController
 *
 * Manage users actions.
 */
class UserController extends AbstractController
{
    /**
     * @var ViewModelBuilderInterface
     */
    private ViewModelBuilderInterface $viewModelBuilder;

    /**
     * UserController constructor.
     *
     * @param ViewModelBuilderInterface $viewModelBuilder
     */
    public function __construct(ViewModelBuilderInterface $viewModelBuilder)
    {
        $this->viewModelBuilder = $viewModelBuilder;
    }

    /**
     * List all users.
     *
     * @return Response
     *
     * @Route("/users", name="user_list", methods={"GET"})
     */
    public function listUserAction(): Response
    {
        return $this->render('user/list.html.twig', [
            'view_model' => $this->viewModelBuilder->create('user_list', [
            ])
        ]);
    }

    /**
     * Create a User entity and save data.
     *
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     *
     * @return RedirectResponse|Response
     *
     * @Route("/users/create", name="user_create")
     */
    public function createUserAction(Request $request, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $user = new User();
        $form = $this->createForm(BaseUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $password = $userPasswordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Update a User entity and save data.
     *
     * @param Request                      $request
     * @param User                         $user
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     *
     * @return RedirectResponse|Response
     *
     * @Route("/users/{id}/edit", name="user_edit")
     */
    public function editUserAction(Request $request, User $user, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $form = $this->createForm(BaseUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $userPasswordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    /**
     * Delete a User entity and remove data.
     *
     * @param User                 $user
     * @param Request              $request
     * @param FormHandlerInterface $deleteUserHandler
     *
     * @return RedirectResponse|Response
     *
     * @Route("/users/{id}/delete", name="user_delete", methods={"DELETE"})
     */
    public function deleteUserAction(
        User $user,
        Request $request,
        FormHandlerInterface $deleteUserHandler
    ): Response {
        // Handle (and validate) corresponding form
        $form = $deleteUserHandler->process($request, [
            'dataModel' => $user
        ]);
        // Perform action(s) on handling success state
        if ($deleteUserHandler->execute()) {
            // Save deletion, and add a successful flash message
            // Then, redirect to user list
            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/list.html.twig', [
            'view_model' => $this->viewModelBuilder->create('delete_user', [
                'form' => $form
            ])
        ]);
    }
}
