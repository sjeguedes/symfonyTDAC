<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Task;
use App\Form\CreateTaskType;
use App\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * @param Request $request
     *
     * @return RedirectResponse|Response
     *
     * @Route("/tasks/create", name="task_create", methods={"GET", "POST"})
     *
     * @throws \Exception
     */
    public function createAction(Request $request): Response
    {
        $task = new Task();
        // Define a particular form type for task creation
        $form = $this->createForm(CreateTaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Associate authenticated user to new task as expected
            $task->setAuthor($this->getUser());
            // Save the new task
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();
            $this->addFlash('success', 'La tâche a été bien été ajoutée.');
            // Redirect to tasks list on success
            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
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
