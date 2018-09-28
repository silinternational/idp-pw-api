<?php
namespace tests\unit\common\components;

use common\components\phoneVerification\Base;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    public function testFormat()
    {
        $this->markTestIncomplete('Need a test double for Nexmo');
        $client = $this->getClient();
        $format = $client->format('14085551212');
        $this->assertEquals('1 (408) 555-1212', $format);
    }

    public function testFormatError()
    {
        $this->markTestIncomplete('Need a test double for Nexmo');
        $client = $this->getClient();
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1469727752);
        $client->format('085551212');
    }

    private function getClient($extra = [])
    {
        $config = include __DIR__ . '/config.local.php';
        $config = array_merge_recursive($config, $extra);
        $client = new Base();
        $client->apiKey = $config['api_key'];
        $client->apiSecret = $config['api_secret'];

        return $client;
    }
}