<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Service\ActionManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

class TaskController extends AbstractController
{
    private $taskRepository;
    private $actionManager;

    public function __construct(TaskRepository $taskRepository, ActionManager $actionManager)
    {
        $this->taskRepository = $taskRepository;
        $this->actionManager = $actionManager;
    }

    /**
     * @Route("/tasks", name="task_list")
     */
    public function listAction(PaginatorInterface $paginator, Request $request)
    {
        $tasks = $paginator->paginate($this->taskRepository->findAll(), $request->query->getInt('page', 1), 9);
        return $this->render('task/list.html.twig', ['tasks' => $tasks]);
    }

    /**
     * @Route("/tasks/terminee", name="task_listisdone")
     */
    public function listIsDone(PaginatorInterface $paginator, Request $request)
    {
        $tasks = $paginator->paginate($this->taskRepository->findByisDone(), $request->query->getInt('page', 1), 9);
        return $this->render('task/listIsDone.html.twig', ['tasks' => $tasks]);
    }

    /**
     * @Route("/tasks/create", name="task_create")
     * @IsGranted("ROLE_USER")
     */
    public function createAction(Request $request)
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/tasks/{id}/edit", name="task_edit")
     * @IsGranted("ROLE_USER")
     */
    public function editAction(Task $task, Request $request)
    {
        $previousUrl = $request->headers->get('referer');

        if ($this->actionManager->actionSecurity($task->getUser())) {
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
        $this->addFlash('error', 'Vous n\'êtes pas l\'auteur de la tâche.');

        return $this->redirect($previousUrl, 301);
    }

    /**
     * @Route("/tasks/{id}/toggle", name="task_toggle")
     * @IsGranted("ROLE_USER")
     */
    public function toggleTaskAction(Task $task)
    {
        $task->toggle(!$task->isDone());
        $this->getDoctrine()->getManager()->flush();

        if ($task->isDone()) {
            $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme terminée.', $task->getTitle()));
        } else {
            $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme non faite.', $task->getTitle()));
        }

        return $this->redirectToRoute('task_list');
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     * @IsGranted("ROLE_USER")
     */
    public function deleteTaskAction(Task $task, Request $request)
    {
        $previousUrl = $request->headers->get('referer');

        if ($this->actionManager->actionSecurity($task->getUser())) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a bien été supprimée.');

            return $this->redirect($previousUrl, 301);
        }

        $this->addFlash('error', 'Vous n\'êtes pas l\'auteur de la tâche.');

        return $this->redirect($previousUrl, 301);
    }
}
