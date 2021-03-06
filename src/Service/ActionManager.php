<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use App\Entity\Task;

class ActionManager extends AbstractController
{
    public function actionSecurity($author)
    {
        $user = $this->getUser();
        $roles = $user->getRoles();

        if ($user === $author || $roles[0] === "ROLE_ADMIN" ) {
            return true;
        }
    }

    public function askIfAnonymous(User $username)
    {
        if ($username->getUsername() === "anonyme"){
            return true;
        }
    }

    public function isExist(int $id, $repository)
    {
        $object = $this->getDoctrine()->getRepository('App:'.$repository)->findOneBy(["id" => $id]);
        return $object;
    }

}