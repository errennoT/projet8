<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function encodePassword($user, $plainPassword)
    {
        return $this->passwordEncoder->encodePassword($user, $plainPassword);
    }

    public function load(ObjectManager $manager)
    {
        $userAnonynous = new User();
        $plainPassword = 'anonyme';
        $newPassword = $this->encodePassword($userAnonynous, $plainPassword);

        $userAnonynous->setUsername('anonyme');
        $userAnonynous->setRoles(['ROLE_ADMIN']);
        $userAnonynous->setPassword($newPassword);
        $userAnonynous->setEmail("anonyme@gmail.com");

        $manager->persist($userAnonynous);

        for ($a = 1; $a <= 3; $a++) {
            $user1 = new User();
            $plainPassword = 'administrateur';
            $newPassword = $this->encodePassword($user1, $plainPassword);

            $user1->setUsername('administrateur' .$a);
            $user1->setRoles(['ROLE_ADMIN']);
            $user1->setPassword($newPassword);
            $user1->setEmail('user1'.$a.'@gmail.com');

            $this->addReference('user1'.$a, $user1);

            $manager->persist($user1);

            $user2 = new User();
            $plainPassword = 'utilisateur';
            $newPassword = $this->encodePassword($user2, $plainPassword);

            $user2->setUsername('utilisateur' .$a);
            $user2->setRoles(['ROLE_USER']);
            $user2->setPassword($newPassword);
            $user2->setEmail('user2'.$a.'@gmail.com');

            $this->addReference('user2'.$a, $user2);

            $manager->persist($user2);
            
            $manager->flush();
        }
    }

}
