<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLogOut()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['username'] = 'utilisateur1';
        $form['password'] = 'utilisateur';
        $client->submit($form);

        $crawler = $client->request('GET', '/');

        $link = $crawler->selectLink('Se déconnecter')->link();
        $crawler = $client->click($link);

        $crawler = $client->followRedirect();

        $this->assertSame(1, $crawler->filter('a:contains("Se connecter")')->count());
    }
}