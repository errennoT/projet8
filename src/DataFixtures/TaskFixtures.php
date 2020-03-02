<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        for ($a = 1; $a <= 10; $a++) {

            $task = new Task();
            $task->setCreatedAt(new \DateTime);
            $task->setTitle('Task n° ' .$a);
            $task->setContent("Isdem diebus Apollinaris Domitiani gener, paulo ante agens palatii Caesaris curam, ad Mesopotamiam missus a socero per militares numeros immodice scrutabatur, an quaedam altiora meditantis iam Galli secreta susceperint scripta, qui conpertis Antiochiae gestis per minorem Armeniam lapsus Constantinopolim petit exindeque per protectores retractus artissime tenebatur.");
            $task->setIsDone(mt_rand(0, 1));
            $task->setUser($this->getReference('user'. mt_rand(1, 2) . mt_rand(1, 3)));
            $this->addReference('task'.$a, $task);

            $manager->persist($task);	
            $manager->flush();
        }
    }

    public function getDependencies()
    {
        return array(
            UserFixtures::class,
        );
    }
}