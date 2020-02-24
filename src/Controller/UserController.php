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

class UserController extends AbstractController
{
    private $userRepository;
    private $actionManager;

    public function __construct(UserRepository $userRepository, ActionManager $actionManager)
    {
        $this->userRepository = $userRepository;
        $this->actionManager = $actionManager;
    }
    /**
     * @Route("/users", name="user_list")
     * @IsGranted("ROLE_ADMIN")
     */
    public function listAction(PaginatorInterface $paginator, Request $request)
    {
        $users = $paginator->paginate($this->userRepository->findWithoutAnonymous("anonyme"), $request->query->getInt('page', 1), 5);
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
        if ($this->actionManager->askIfAnonymous($user)){
            $this->addFlash('error', "Impossible de modifier cet utilisateur.");
            return $this->redirectToRoute('user_list');
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

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    /**
     * @Route("/users/{id}/delete", name="user_delete")
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteAction(User $user, Request $request)
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token')) && $user->getUsername() !== "anonyme") {
            
            $tasks = $user->getTasks();
            foreach ($tasks as $task){
                $task->setUser($this->userRepository->findOneBy(['username' => 'anonyme']));
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            
            return $this->redirectToRoute('user_list');
        }

        $this->addFlash('error', 'Impossible de supprimer cet utilisateur.');
            
        return $this->redirectToRoute('user_list');
    }
}
