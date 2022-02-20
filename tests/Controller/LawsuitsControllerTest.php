<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LawsuitsControllerTest extends WebTestCase
{

    // Test basico de respuesta
    public function testUrl(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '?plaintiff=kvn&defendant=KKn');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h1', 'Welcome to Lawsuits!');
    }

    // Test de resultados mostrados
    public function testResults(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '?plaintiff=k&defendant=N');

        $this->assertCount(2, $crawler->filter('.result'), "\e[0;31m ERROR: se esperaban 2 resultados. \e[m");
    }

    // Test de analisis de Jokers
    public function testJokers(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '?plaintiff=k%23&defendant=N%23');

        $this->assertCount(2, $crawler->filter('.joker'), "\e[0;31m ERROR: se esperaban 2 resultados. \e[m");
    }

    // Test para confirmar que me muestra 2 empates
    public function testTai(): void
    {

        $client = static::createClient();
        $crawler = $client->request('GET', '?plaintiff=k&defendant=K');

        $this->assertCount(2, $crawler->filter('.result'), "\e[0;31m ERROR: se esperaban 2 resultados. \e[m");

        if(strpos($crawler->filter('.result')->eq(0)->text(), "they have 5 points and tie") === FALSE) {
            $this->fail("\e[0;31m ERROR: ambos resultados deben ser empate. \e[m");
        }

        if(strpos($crawler->filter('.result')->eq(1)->text(), "they have 5 points and tie") === FALSE) {
            $this->fail("\e[0;31m ERROR: ambos resultados deben ser empate. \e[m");
        }

    }
}
