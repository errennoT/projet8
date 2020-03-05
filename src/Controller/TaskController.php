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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TaskController extends AbstractController
{
    private $taskRepository;
    private $actionManager;
    private $cache;

    public function __construct(TaskRepository $taskRepository, ActionManager $actionManager,  CacheInterface $cache)
    {
        $this->taskRepository = $taskRepository;
        $this->actionManager = $actionManager;
        $this->cache = $cache;
    }

    /**
     * @Route("/tasks", name="task_list")
     */
    public function listAction(PaginatorInterface $paginator, Request $request)
    {
        $tasksInCache = $this->cache->get(
            'tasks_in_cache',
            function (ItemInterface $item) {
                $item->expiresAfter(86400);
                $tasks = $this->taskRepository->findAll();
                return $tasks;
            }
        );

        $tasks = $paginator->paginate($tasksInCache, $request->query->getInt('page', 1), 9);
        return $this->render('task/list.html.twig', ['tasks' => $tasks]);
    }

    /**
     * @Route("/tasks/done", name="task_listisdone")
     */
    public function listIsDone(PaginatorInterface $paginator, Request $request)
    {
        $tasksDoneInCache = $this->cache->get(
            'tasks_done_in_cache',
            function (ItemInterface $item) {
                $item->expiresAfter(86400);
                $tasks = $this->taskRepository->findByisDone();
                return $tasks;
            }
        );

        $tasks = $paginator->paginate($tasksDoneInCache, $request->query->getInt('page', 1), 9);
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

            $this->cache->delete('tasks_in_cache');
            $this->cache->delete('tasks_done_in_cache');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/tasks/{id}/edit", name="task_edit")
     * @IsGranted("ROLE_USER")
     */
    public function editAction(Task $task = null, Request $request, $id)
    {
        if ($this->actionManager->isExist($id, "Task")) {

            $previousUrl = $request->headers->get('referer');

            if ($this->actionManager->actionSecurity($task->getUser())) {
                $form = $this->createForm(TaskType::class, $task);

                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $this->getDoctrine()->getManager()->flush();

                    $this->addFlash('success', 'La tâche a bien été modifiée.');

                    $this->cache->delete('tasks_in_cache');
                    $this->cache->delete('tasks_done_in_cache');

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

        return $this->render('errors/error.html.twig', ['error' => "La tâche avec l'id $id n'existe pas"]);
    }

    /**
     * @Route("/tasks/{id}/toggle", name="task_toggle")
     * @IsGranted("ROLE_USER")
     */
    public function toggleTaskAction(Task $task = null, Request $request, $id)
    {
        if ($this->actionManager->isExist($id, "Task")) {

            $previousUrl = $request->headers->get('referer');

            $task->toggle(!$task->isDone());
            $this->getDoctrine()->getManager()->flush();

            if ($task->isDone()) {
                $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme terminée.', $task->getTitle()));
            } else {
                $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme non faite.', $task->getTitle()));
            }

            $this->cache->delete('tasks_in_cache');
            $this->cache->delete('tasks_done_in_cache');

            return $this->redirect($previousUrl, 301);
        }

        return $this->render('errors/error.html.twig', ['error' => "La tâche avec l'id $id n'existe pas"]);
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     * @IsGranted("ROLE_USER")
     */
    public function deleteTaskAction(Task $task = null, Request $request, $id)
    {
        if ($this->actionManager->isExist($id, "Task")) {

            $previousUrl = $request->headers->get('referer');

            if ($this->actionManager->actionSecurity($task->getUser())) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($task);
                $em->flush();

                $this->addFlash('success', 'La tâche a bien été supprimée.');

                $this->cache->delete('tasks_in_cache');
                $this->cache->delete('tasks_done_in_cache');

                return $this->redirect($previousUrl, 301);
            }
            $this->addFlash('error', 'Vous n\'êtes pas l\'auteur de la tâche.');

            return $this->redirect($previousUrl, 301);
        }

        return $this->render('errors/error.html.twig', ['error' => "La tâche avec l'id $id n'existe pas"]);
    }
}
