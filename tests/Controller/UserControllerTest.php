<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testlistAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'administrateur1';
        $form['password'] = 'administrateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/users');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testCreationAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'administrateur1';
        $form['password'] = 'administrateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/users/create');

        $form = $crawler->selectButton('Ajouter')->form();

        $buttonCrawlerNode = $crawler->selectButton('Ajouter');
        $form = $buttonCrawlerNode->form([
            'user[username]'    => 'usertest',
            'user[password][first]'    => 'usertest',
            'user[password][second]'    => 'usertest',
            'user[email]'    => 'usertest@gmail.com',
            'user[roles]'    => 'ROLE_USER',
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

        $crawler = $client->request('GET', '/users');

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $client->click($link);

        $buttonCrawlerNode = $crawler->selectButton('Modifier');
        $form = $buttonCrawlerNode->form([
            'user[password][first]'    => 'administrateur',
            'user[password][second]'    => 'administrateur',
            'user[roles]'    => 'ROLE_ADMIN',
        ]);

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

        $crawler = $client->request('GET', '/users');

        $form = $crawler->selectButton('Supprimer')->form();
        $client->submit($form);

        $crawler = $client->followRedirect();

        $this->assertSame(1, $crawler->filter('div.alert.alert-success')->count());
    }
}