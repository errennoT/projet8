<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    public function testListAction()
    {
        $client = static::createClient();
        $client->request('GET', '/tasks');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testListIsDone()
    {
        $client = static::createClient();
        $client->request('GET', '/tasks/terminee');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCreateAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'utilisateur1';
        $form['password'] = 'utilisateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/tasks/create');

        $form = $crawler->selectButton('Ajouter')->form();

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');
        $form = $buttonCrawlerNode->form([
            'task[title]'    => 'ajoutdunetache',
            'task[content]'    => 'Test ajout d\'une tâche',
        ]);

        $client->submit($form);

        $crawler = $client->followRedirect();

        $this->assertSame(1, $crawler->filter('div.alert.alert-success')->count());
    }

    public function testEditAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'administrateur1';
        $form['password'] = 'administrateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/tasks');

        $link = $crawler->selectLink('Task n° 10')->link();
        $crawler = $client->click($link);

        $form = $crawler->selectButton('Modifier')->form();

        $client->submit($form);

        $crawler = $client->followRedirect();

        $this->assertSame(1, $crawler->filter('div.alert.alert-success')->count());
    }

    public function testToggleTaskAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'utilisateur1';
        $form['password'] = 'utilisateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/tasks');

        $form = $crawler->selectButton('Marquer non terminée')->form();
        $client->submit($form);

        $crawler = $client->followRedirect();

        $this->assertSame(1, $crawler->filter('div.alert.alert-success')->count());
    }

    public function testDeleteTaskAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'administrateur1';
        $form['password'] = 'administrateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/tasks');

        $form = $crawler->selectButton('Supprimer')->form();
        $client->submit($form);

        $crawler = $client->followRedirect();

        $this->assertSame(1, $crawler->filter('div.alert.alert-success')->count());
    }
}