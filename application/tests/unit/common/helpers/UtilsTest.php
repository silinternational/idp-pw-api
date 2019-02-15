<?php
namespace tests\unit\common\helpers;

use Sil\Codeception\TestCase\Test;
use common\helpers\Utils;
use yii\web\Request;

class UtilsTest extends Test
{
    public function testUidRegexGenerateRandomString()
    {
        $regex = '/' . Utils::UID_REGEX . '/';
        for ($i = 0; $i < 50; $i++) {
            $uid = Utils::generateRandomString();
            $this->assertRegExp($regex, $uid);
        }
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

    public function testGetFrontendConfig()
    {
        \Yii::$app->params = [
            'idpName' => 'idp',
            'idpDisplayName' => 'My IdP',
            'idpUsernameHint' => 'IdP Account',
            'adminEmail' => 'admin@domain.com',
            'fromEmail' => 'from@domain.com',
            'fromName' => 'From Me',
            'helpCenterUrl' => 'https://url',
            'ui_url' => 'https://ui',
            'logoUrl' => 'http://logoUrl',
            'reset' => [
                'lifetimeSeconds' => 3600, // 1 hour
                'disableDuration' => 900, // 15 minutes
                'codeLength' => 6,
                'maxAttempts' => 10,
            ],
            'passwordLifetime' => 15552000, // 6 months
            'password' => [
                'minLength' => [
                    'value' => 10,
                    'phpRegex' => '/.{10,}/',
                    'jsRegex' => '.{10,}',
                    'enabled' => true
                ],
                'maxLength' => [
                    'value' => 255,
                    'phpRegex' => '/.{0,255}/',
                    'jsRegex' => '.{0,255}',
                    'enabled' => true
                ],
                'zxcvbn' => [
                    'minScore' => 2,
                    'enabled' => true,
                    'apiBaseUrl' => 'http://zxcvbn',
                ]
            ],
            'recaptcha' => [
                'siteKey' => 'key',
                'secretKey' => 'secret',
            ],
            'support' => [
                'phone' => '123-123-1234',
                'email' => 'email@domain.com',
                'url' => 'http://url',
                'feedbackUrl' => null,
            ],
        ];

        $expectedZxcvbn = [
            'minScore' => 2,
        ];

        $params = \Yii::$app->params;
        $config = Utils::getFrontendConfig();
        $this->assertEquals($params['idpDisplayName'], $config['idpName']);
        $this->assertEquals($params['idpUsernameHint'], $config['idpUsernameHint']);
        $this->assertEquals($params['recaptcha']['siteKey'], $config['recaptchaKey']);
        $this->assertEquals($expectedZxcvbn, $config['password']['zxcvbn']);
        $this->assertTrue(is_array($config['password']));

        $expectedSupport = [
            'phone' => '123-123-1234',
            'email' => 'email@domain.com',
            'url' => 'http://url',
        ];

        $this->assertEquals($expectedSupport, $config['support']);
    }

    public function testGetIso8601()
    {
        $expected = '2016-06-15T13:09:28Z';
        $timestamp = 1465996168;

        $this->assertEquals($expected, Utils::getIso8601($timestamp));
    }

    public function testIsArrayEntryTruthy()
    {
        $this->assertTrue(Utils::isArrayEntryTruthy(['key' => true], 'key'));
        $this->assertTrue(Utils::isArrayEntryTruthy(['key' => 'string'], 'key'));
        $this->assertTrue(Utils::isArrayEntryTruthy(['key' => ['array']], 'key'));
        $this->assertTrue(Utils::isArrayEntryTruthy(['key' => 1], 'key'));
        $this->assertFalse(Utils::isArrayEntryTruthy(['key' => false], 'key'));
        $this->assertFalse(Utils::isArrayEntryTruthy(['key' => ''], 'key'));
        $this->assertFalse(Utils::isArrayEntryTruthy(['key' => null], 'key'));
        $this->assertFalse(Utils::isArrayEntryTruthy(['key' => 0], 'key'));
    }

    public function testGetFriendlyDate()
    {
        /*
         * Test with timestamp
         */
        $expected = 'Monday July 18, 2016 6:17PM UTC';
        $timestamp = 1468865838;

        $this->assertEquals($expected, Utils::getFriendlyDate($timestamp));

        /*
         * Test with string
         */
        $expected = 'Monday July 18, 2016 2:17PM UTC';
        $string = '2016-07-18 14:17:00';

        $this->assertEquals($expected, Utils::getFriendlyDate($string));
    }

    public function testGetDatetime()
    {
        $expected = '2016-07-18 18:17:18';
        $timestamp = 1468865838;

        $this->assertEquals($expected, Utils::getDatetime($timestamp));
    }
}
