<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActionManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UserController extends AbstractController
{
    private $userRepository;
    private $actionManager;
    private $cache;

    public function __construct(UserRepository $userRepository, ActionManager $actionManager, CacheInterface $cache)
    {
        $this->userRepository = $userRepository;
        $this->actionManager = $actionManager;
        $this->cache = $cache;
    }
    /**
     * @Route("/users", name="user_list")
     * @IsGranted("ROLE_ADMIN")
     */
    public function listAction(PaginatorInterface $paginator, Request $request)
    {
        $usersInCache = $this->cache->get(
            'users_in_cache',
            function (ItemInterface $item) {
                $item->expiresAfter(86400);
                $users = $this->userRepository->findWithoutAnonymous("anonyme");
                return $users;
            }
        );

        $users = $paginator->paginate($usersInCache, $request->query->getInt('page', 1), 5);
        return $this->render('user/list.html.twig', ['users' => $users]);
    }

    /**
     * @Route("/users/create", name="user_create")
     * @IsGranted("ROLE_ADMIN")
     */
    public function createAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $roles = $request->request->get('user')['roles'];
            $user->setRoles([$roles]);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            $this->cache->delete('users_in_cache');

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/users/{id}/edit", name="user_edit")
     * @IsGranted("ROLE_ADMIN")
     */
    public function editAction(User $user, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $previousUrl = $request->headers->get('referer');

        if ($this->actionManager->askIfAnonymous($user)){
            $this->addFlash('error', "Impossible de modifier cet utilisateur.");
            return $this->redirect($previousUrl, 301);
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $roles = $request->request->get('user')['roles'];
            $user->setRoles([$roles]);

            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié");

            $this->cache->delete('users_in_cache');

            return $this->redirect($previousUrl, 301);
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    /**
     * @Route("/users/{id}/delete", name="user_delete")
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteAction(User $user, Request $request)
    {
        $previousUrl = $request->headers->get('referer');
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token')) && $user->getUsername() !== "anonyme") {
            
            $tasks = $user->getTasks();
            foreach ($tasks as $task){
                $task->setUser($this->userRepository->findOneBy(['username' => 'anonyme']));
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');

            $this->cache->delete('users_in_cache');
            
            return $this->redirect($previousUrl, 301);
        }

        $this->addFlash('error', 'Impossible de supprimer cet utilisateur.');
            
        return $this->redirect($previousUrl, 301);
    }
}
