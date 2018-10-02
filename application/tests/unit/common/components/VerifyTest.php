<?php
namespace tests\unit\common\components;

use common\components\phoneVerification\Verify;
use PHPUnit\Framework\TestCase;

class VerifyTest extends TestCase
{
    public function testVerify()
    {
        $this->markTestIncomplete('Need a test double for Nexmo');
        $config = include __DIR__ . '/config.local.php';
        $client = $this->getClient();
        $response = $client->send($config['number'], '1111');
        echo $response;
        $this->assertNotNull($response);
    }

    public function testCheck()
    {
        $this->markTestIncomplete('Need a test double for Nexmo');
        $config = include __DIR__ . '/config.local.php';
        $client = $this->getClient();
        $response = $client->verify($config['request_id'], $config['code']);
        $this->assertTrue($response);
    }

    private function getClient($extra = [])
    {
        $config = include __DIR__ . '/config.local.php';
        $config = array_merge_recursive($config, $extra);
        $client = new Verify();
        $client->apiKey = $config['api_key'];
        $client->apiSecret = $config['api_secret'];
        $client->brand = 'Verify Test';

        return $client;
    }
}