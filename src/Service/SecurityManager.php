<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SecurityManager extends AbstractController
{
    public function actionSecurity($author)
    {
        $user = $this->getUser();
        $roles = $user->getRoles();

        if ($user === $author || $roles[0] === "ROLE_ADMIN" ) {
            return true;
        }
    }


}