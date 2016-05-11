<?php
namespace tests\unit\common\helpers;

use yii\codeception\TestCase;
use common\helpers\Utils;
use yii\web\Request;

class UtilsTest extends TestCase
{
    public function testUidRegexGenerateRandomString()
    {
        $regex = '/' . Utils::UID_REGEX . '/';
        for ($i = 0; $i < 50; $i++) {
            $uid = Utils::generateRandomString();
            $this->assertRegExp($regex, $uid);
        }
    }

    public function testMaskPhone()
    {
        $phone1 = '1234567890';
        $expected1 = '#######890';
        $this->assertEquals($expected1, Utils::maskPhone($phone1));

        $phone2 = '77,8889123456';
        $expected2 = '+77 #######456';
        $this->assertEquals($expected2, Utils::maskPhone($phone2));

    }
    
    public function testMaskEmail()
    {
        $email1 = 'abc@domain.com';
        $expected1 = 'a*c@d*****.c**';
        $this->assertEquals($expected1, Utils::maskEmail($email1));

        $email2 = 'first_last@myco.org';
        $expected2 = 'f****_l**t@m***.o**';
        $this->assertEquals($expected2, Utils::maskEmail($email2));
    }

    public function testGetRandomDigits()
    {
        for ($i = 4; $i < 32; $i++) {
            $value = Utils::getRandomDigits($i);
            $regex = '/^[0-9]{' . $i . '}$/';
            $this->assertRegExp($regex, $value);
        }
    }
    
    public function testIsValidIpAddress()
    {
        $this->assertTrue(Utils::isValidIpAddress('127.0.0.1'));
        $this->assertTrue(Utils::isValidIpAddress('fe80::58bb:d8ff:feec:ff6c'));
        $this->assertFalse(Utils::isValidIpAddress('not an ip address'));
        $this->assertFalse(Utils::isValidIpAddress('10.256.123.123'));
    }

    
}