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

    public function testGetFrontendConfig()
    {
        \Yii::$app->params = [
            'idpName' => 'My IdP',
            'idpUsernameHint' => 'IdP Account',
            'adminEmail' => 'admin@domain.com',
            'fromEmail' => 'from@domain.com',
            'fromName' => 'From Me',
            'helpCenterUrl' => 'https://url',
            'ui_url' => 'https://ui',
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
                'minNum' => [
                    'value' => 2,
                    'phpRegex' => '/(\d.*){2,}/',
                    'jsRegex' => '(\d.*){2,}',
                    'enabled' => true
                ],
                'minUpper' => [
                    'value' => 0,
                    'phpRegex' => '/([A-Z].*){0,}/',
                    'jsRegex' => '([A-Z].*){0,}',
                    'enabled' => false
                ],
                'minSpecial' => [
                    'value' => 0,
                    'phpRegex' => '/([\W_].*){0,}/',
                    'jsRegex' => '([\W_].*){0,}',
                    'enabled' => false
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

        $params = \Yii::$app->params;
        $config = Utils::getFrontendConfig();
        $this->assertEquals($params['idpName'], $config['idpName']);
        $this->assertEquals($params['idpUsernameHint'], $config['idpUsernameHint']);
        $this->assertEquals($params['recaptcha']['siteKey'], $config['recaptchaKey']);
        $this->assertEquals($config['password']['zxcvbn'], $params['password']['zxcvbn']);
        $this->assertTrue(is_array($config['password']));

        $expectedSupport = [
            'phone' => '123-123-1234',
            'email' => 'email@domain.com',
            'url' => 'http://url',
        ];

        $this->assertEquals($expectedSupport, $config['support']);
    }

    
}