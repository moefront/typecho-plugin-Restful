<?php
namespace MoeFront\RestfulTests;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Medoo\Medoo;
use PHPUnit\Framework\TestCase;

if (php_sapi_name() !== 'cli') {
    exit;
}

class RestfulTest extends TestCase
{
    private static $client;

    private static $db;

    public static function setUpBeforeClass()
    {
        self::$client = new Client(array(
            'base_uri' => 'http://' . getenv('WEB_SERVER_HOST') . ':' . getenv('WEB_SERVER_PORT'),
            'http_errors' => false,
        ));

        self::$db = new Medoo(array(
            'database_type' => 'mysql',
            'database_name' => getenv('MYSQL_DB'),
            'server' => getenv('MYSQL_HOST'),
            'username' => getenv('MYSQL_USER'),
            'password' => getenv('MYSQL_PWD'),
        ));
    }

    public function testPosts()
    {
        $response = self::$client->get('/api/posts');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('page', $result['data']);
        $this->assertArrayHasKey('pages', $result['data']);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('pageSize', $result['data']);
        $this->assertArrayHasKey('dataSet', $result['data']);
    }

    public function testPages()
    {
        $response = self::$client->get('/api/pages');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('dataSet', $result['data']);
    }

    public function testCategories()
    {
        $response = self::$client->get('/api/categories');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
    }

    public function testTags()
    {
        $response = self::$client->get('/api/tags');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
    }

    public function testPost()
    {
        $response = self::$client->get('/api/post', array('query' => array('cid' => 1)));
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue(is_array($result['data']));
    }

    public function testComments()
    {
        $response = self::$client->get('/api/comments', array('query' => array('cid' => 1)));
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('page', $result['data']);
        $this->assertArrayHasKey('pages', $result['data']);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('pageSize', $result['data']);
        $this->assertArrayHasKey('dataSet', $result['data']);
    }

    public function testComment()
    {
        // without token
        $response = self::$client->post('/api/comment', array(
            RequestOptions::JSON => array(
                'cid' => 1,
                'text' => '233',
                'author' => 'test',
                'mail' => 'test@qq.com',
            ),
        ));
        $result = json_decode($response->getBody(), true);
        $this->assertEquals('error', $result['status']);

        // with token and invalid form value
        $response = self::$client->get('/api/post', array('query' => array('cid' => 1)));
        $result = json_decode($response->getBody(), true);
        $response = self::$client->post('/api/comment', array(
            RequestOptions::JSON => array(
                'cid' => 1,
                'text' => '233',
                'author' => 'test',
                'mail' => 'testqq.com',
                'token' => $result['data']['csrfToken'],
            ),
        ));
        $result = json_decode($response->getBody(), true);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('邮箱地址不合法', $result['message']);

        // insert a normal user comment
        $response = self::$client->get('/api/post', array('query' => array('cid' => 1)));
        $result = json_decode($response->getBody(), true);
        $response = self::$client->post('/api/comment', array(
            RequestOptions::JSON => array(
                'cid' => 1,
                'text' => '233',
                'author' => 'test',
                'mail' => 'test@qq.com',
                'token' => $result['data']['csrfToken'],
            ),
        ));
        $count = self::$db->count('typecho_comments', array(
            'cid' => 1,
            'text' => '233',
            'author' => 'test',
            'mail' => 'test@qq.com',
        ));
        $this->assertEquals(1, $count);
    }

    public function testSettings()
    {
        $response = self::$client->get('/api/settings');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('title', $result['data']);
        $this->assertArrayHasKey('description', $result['data']);
        $this->assertArrayHasKey('keywords', $result['data']);
        $this->assertArrayHasKey('timezone', $result['data']);
    }

    public function testUsers()
    {
        $response = self::$client->get('/api/users?uid=1');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('dataSet', $result['data']);
        $this->assertArrayHasKey('posts', $result['data']['dataSet'][0]);
    }

    public function testArchives()
    {
        $response = self::$client->get('/api/archives?showContent=true');
        $result = json_decode($response->getBody(), true);

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('dataSet', $result['data']);
    }
}
