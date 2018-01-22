<?php
namespace MoeFront\RestfulTests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class RestfulTest extends TestCase
{
    public function testSettings()
    {
        $client = new Client();

        $response = $client->get('http://' . getenv('WEB_SERVER_HOST') . ':' . getenv('WEB_SERVER_PORT') . '/api/settings');
        $body = (string) $response->getBody();
        $body = json_decode($body, true);

        $this->assertTrue(isset($body['data']));
    }
}
