<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Factory\ModelFactoryInterface;
use App\Entity\Task;
use App\Form\Handler\FormHandlerInterface;
use App\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * List all tasks.
     *
     * @Route("/tasks", name="task_list")
     */
    public function listAction()
    {
        return $this->render('task/list.html.twig', ['tasks' => $this->getDoctrine()->getRepository(Task::class)->findAll()]);
    }

    /**
     * Create a Task entity ans save data.
     *
     * @param Request               $request
     * @param FormHandlerInterface  $createTaskHandler
     * @param ModelFactoryInterface $modelFactory
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/create", name="task_create", methods={"GET", "POST"})
     *
     * @throws \Exception
     */
    public function createAction(
        Request $request,
        FormHandlerInterface $createTaskHandler,
        ModelFactoryInterface $modelFactory
    ): Response {
        // Validate corresponding form
        $form = $createTaskHandler->process($request, [
            'dataModel' => $modelFactory->create('task')
        ]);
        // Perform action(s) on success
        if ($createTaskHandler->isSuccess()) {
            // Associate authenticated user to new task as expected, and save form data
            // Send a flash message
            $createTaskHandler->executeOnSuccess();
            // Redirect to tasks list
            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Update a Task entity and save modified data.
     *
     * @param Task    $task
     * @param Request $request
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/{id}/edit", name="task_edit")
     */
    public function editAction(Task $task, Request $request)
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * Toggle Task "isDone" state.
     *
     * @param Task $task
     *
     * @return RedirectResponse
     *
     * @Route("/tasks/{id}/toggle", name="task_toggle")
     */
    public function toggleTaskAction(Task $task)
    {
        $task->toggle(!$task->isDone());
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    /**
     * Delete a Task entity and remove data.
     *
     * @param Task $task
     *
     * @return RedirectResponse
     *
     * @Route("/tasks/{id}/delete", name="task_delete")
     */
    public function deleteTaskAction(Task $task)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
