<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Factory\DataModelFactoryInterface;
use App\Entity\Task;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Type\DeleteTaskType;
use App\Form\Type\ToggleTaskType;
use App\View\Builder\ViewModelBuilderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TaskController
 *
 * Manage tasks actions.
 */
class TaskController extends AbstractController
{
    /**
     * @var ViewModelBuilderInterface
     */
    private ViewModelBuilderInterface $viewModelBuilder;

    /**
     * TaskController constructor.
     *
     * @param ViewModelBuilderInterface $viewModelBuilder
     */
    public function __construct(ViewModelBuilderInterface $viewModelBuilder)
    {
        $this->viewModelBuilder = $viewModelBuilder;
    }

    /**
     * List all tasks.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/tasks", name="task_list", methods={"GET"})
     */
    public function listTaskAction(Request $request): Response
    {
        // In order to select tasks, "isDone" status filter may exist!
        return $this->render('task/list.html.twig', [
            'view_model' => $this->viewModelBuilder->create('task_list', [
                'listStatus' => $request->query->get('listStatus')
            ])
        ]);
    }

    /**
     * Create a Task entity ans save data.
     *
     * @param Request                   $request
     * @param FormHandlerInterface      $createTaskHandler
     * @param DataModelFactoryInterface $dataModelFactory
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/create", name="task_create", methods={"GET", "POST"})
     *
     * @throws \Exception
     */
    public function createTaskAction(
        Request $request,
        FormHandlerInterface $createTaskHandler,
        DataModelFactoryInterface $dataModelFactory
    ): Response {
        // Handle (and validate) corresponding form
        $form = $createTaskHandler->process($request, [
            'dataModel' => $dataModelFactory->create('task')
        ]);
        // Perform action(s) on handling success state
        if ($createTaskHandler->execute()) {
            // Associate authenticated user to new task and add a successful flash message
            // Then, redirect to task list
            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'view_model' => $this->viewModelBuilder->create('create_task', [
                'form' => $form
            ])
        ]);
    }

    /**
     * Update a Task entity and save modified data.
     *
     * @param Task                 $task
     * @param Request              $request
     * @param FormHandlerInterface $editTaskHandler
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/{id}/edit", name="task_edit", methods={"GET", "POST"})
     */
    public function editTaskAction(
        Task $task,
        Request $request,
        FormHandlerInterface $editTaskHandler
    ): Response {
        // Handle (and validate) corresponding form
        $form = $editTaskHandler->process($request, [
            'dataModel' => $task
        ]);
        // Perform action(s) on handling success state
        if ($editTaskHandler->execute()) {
            // Save change(s), specify authenticated user as task last editor, and add a successful flash message
            // Then, redirect to task list
            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'view_model' => $this->viewModelBuilder->create('edit_task', [
                'form' => $form,
                'task' => $task
            ])
        ]);
    }

    /**
     * Toggle Task "isDone" state.
     *
     * @param Task                 $task
     * @param Request              $request
     * @param FormHandlerInterface $toggleTaskHandler
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/{id}/toggle", name="task_toggle", methods={"PATCH"})
     */
    public function toggleTaskAction(
        Task $task,
        Request $request,
        FormHandlerInterface $toggleTaskHandler
    ): Response {
        // Handle (and validate) corresponding form
        $form = $toggleTaskHandler->process($request, [
            'dataModel' => $task
        ]);
        // Perform action(s) on handling success state
        if ($toggleTaskHandler->execute()) {
            // Save state change, and add a successful flash message
            // Then, redirect to task list ("isDone" status filter may exist!)
            return $this->redirectToRoute('task_list', [
                'listStatus' => $request->query->get('listStatus')
            ]);
        }

        return $this->render('task/list.html.twig', [
            'view_model' => $this->viewModelBuilder->create('toggle_task', [
                'form' => $form,
                'listStatus' => $request->query->get('listStatus')
            ])
        ]);
    }

    /**
     * Delete a Task entity and remove data.
     *
     * @param Task                 $task
     * @param Request              $request
     * @param FormHandlerInterface $deleteTaskHandler
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/{id}/delete", name="task_delete", methods={"DELETE"})
     *
     * An authenticated user can delete one of his own tasks or
     * an admin can delete a task without author.
     * @Security(
        "is_granted('USER_CAN_DELETE_IT_AS_AUTHOR', task) or
         is_granted('ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR', task)"
       )
     *
     * @see https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html
     */
    public function deleteTaskAction(
        Task $task,
        Request $request,
        FormHandlerInterface $deleteTaskHandler
    ): Response {
        // Handle (and validate) corresponding form
        $form = $deleteTaskHandler->process($request, [
            'dataModel' => $task
        ]);
        // Perform action(s) on handling success state
        if ($deleteTaskHandler->execute()) {
            // Save deletion, and add a successful flash message
            // Then, redirect to task list ("isDone" status filter may exist!)
            return $this->redirectToRoute('task_list', [
                'listStatus' => $request->query->get('listStatus')
            ]);
        }

        return $this->render('task/list.html.twig', [
            'view_model' => $this->viewModelBuilder->create('delete_task', [
                'form' => $form,
                'listStatus' => $request->query->get('listStatus')
            ])
        ]);
    }

    /**
     * Load a task particular form view via AJAX for better performance.
     *
     * @param                      Task $task
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     *
     * @return Response
     *
     * @Route("tasks/{id}/load-{type<toggle|deletion>}-form", name="task_load_form", methods={"GET"})
     */
    public function loadTaskForm(
        Task $task,
        Request $request,
        FormFactoryInterface $formFactory
    ): Response {
        if (!$request->isXmlHttpRequest()) {
            throw new \BadMethodCallException('This TaskController method cannot be called without AJAX!');
        }
        $actionType = $request->attributes->get('type');
        // Create named form
        $form = $formFactory->createNamed(
            ('toggle' === $actionType ? 'toggle_task' : 'delete_task') . '_' . $task->getId(),
            'toggle' === $actionType ? toggleTaskType::class : deleteTaskType::class
        );

        return $this->render('_partials/_task_' . $actionType . '_form.html.twig', [
             // Create a particular Symfony task form view
            $actionType . '_form' => $form->createView(),
            'task'                => $task
        ]);
    }
}
