<?php
namespace tests\unit\common\models;

use common\helpers\Utils;
use common\models\Method;
use common\models\User;
use yii\codeception\DbTestCase;

use tests\helpers\EmailUtils;
use tests\unit\fixtures\common\models\UserFixture;
use tests\unit\fixtures\common\models\MethodFixture;

/**
 * Class MethodTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method Method methods($key)
 */
class MethodTest extends DbTestCase
{
    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
            'methods' => MethodFixture::className(),
        ];
    }

    public function testDefaultValues()
    {
        $user1 = $this->users('user1');
        $method = new Method();
        $method->user_id = $user1->id;
        $method->type = Method::TYPE_EMAIL;
        $method->value = 'email-1456765289@domain.org';
        if ( ! $method->save()) {
            $this->fail('Failed to save Method when testing default values: ' .
                print_r($method->getFirstErrors(), true));
        }

        $this->assertNotNull($method->id);
        $this->assertEquals(32, strlen($method->uid));
        $this->assertEquals(0, $method->verified);
        $this->assertNotNull($method->verification_code);
        $this->assertEquals(\Yii::$app->params['reset']['codeLength'], strlen($method->verification_code));
        $this->assertEquals(0, $method->verification_attempts);
        $this->assertNotNull($method->verification_expires);
        $this->assertNotNull($method->created);
    }

    public function testGetMaskedValuePhone()
    {
        $method = $this->methods('method1');
        $this->assertEquals('+1 #######890', $method->getMaskedValue());
    }

    public function testGetMaskedValueEmail()
    {
        $method = $this->methods('method2');
        $this->assertEquals('e**************9@d*****.o**', $method->getMaskedValue());
    }

    public function testRuleValidateValueAsPhone()
    {
        $method = $this->methods('method1');
        $this->assertTrue($method->validate(['value']));

        $method->value = 'email@domain.com';
        $this->assertFalse($method->validate(['value']));
    }

    public function testCreateAndSendVerificationEmail()
    {
        EmailUtils::removeEmailFiles();
        $user = $this->users('user1');

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $method = Method::createAndSendVerification(
            $user->id,
            Method::TYPE_EMAIL,
            'unique-1461443608@email.com'
        );

        $this->assertEquals(1, EmailUtils::getEmailFilesCount());
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($method->verification_code));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($method->value));

    }

    public function testCreateAndSendVerificationPhone()
    {
        $user = $this->users('user1');
        $mockPhones = include __DIR__ . '/../../../mock/phone/data.php';
        $method = Method::createAndSendVerification(
            $user->id,
            Method::TYPE_PHONE,
            $mockPhones[0]['number']
        );

        $this->assertEquals($mockPhones[0]['code'], $method->verification_code);
    }

    public function testCreateAndSendVerificationInvalidType()
    {
        $this->setExpectedException('\Exception', '', 1461375342);
        Method::createAndSendVerification(
            1,
            'invalid type',
            'value'
        );
    }

    public function testCreateAndSendVerificationInvalidEmail()
    {
        $this->setExpectedException('\Exception', '', 1461459797);
        Method::createAndSendVerification(
            1,
            Method::TYPE_EMAIL,
            'not-a-email'
        );
    }

    public function testCreateAndSendVerificationInvalidPhone()
    {
        $this->setExpectedException('\Exception', '', 1461375342);
        Method::createAndSendVerification(
            1,
            Method::TYPE_PHONE,
            'not-a-phone'
        );
    }

    public function testValidateAndSetAsVerifiedValid()
    {
        $user = $this->users('user1');
        $mockPhones = include __DIR__ . '/../../../mock/phone/data.php';
        $method = Method::createAndSendVerification(
            $user->id,
            Method::TYPE_PHONE,
            $mockPhones[0]['number']
        );

        $this->assertEquals($mockPhones[0]['code'], $method->verification_code);

        $method->validateAndSetAsVerified($mockPhones[0]['code']);

        $this->assertEquals(1, $method->verified);
        $this->assertNull($method->verification_code);
        $this->assertNull($method->verification_expires);
    }

    public function testValidateAndSetAsVerifiedInvalid()
    {
        $user = $this->users('user1');
        $mockPhones = include __DIR__ . '/../../../mock/phone/data.php';
        $method = Method::createAndSendVerification(
            $user->id,
            Method::TYPE_PHONE,
            $mockPhones[0]['number']
        );

        $this->assertEquals($mockPhones[0]['code'], $method->verification_code);
        $this->assertEquals(0, $method->verification_attempts);

        $this->setExpectedException('\Exception', '', 1461442988);
        $method->validateAndSetAsVerified('asdf1234');

        $this->assertEquals(0, $method->verified);
        $this->assertEquals(1, $method->verification_attempts);
        $this->assertNotNull($method->verification_code);
        $this->assertNotNull($method->verification_expires);

        $this->setExpectedException('\Exception', '', 1461442988);
        $method->validateAndSetAsVerified('asdf1234');
        $this->assertEquals(2, $method->verification_attempts);
    }

    public function testDeleteExpiredUnverifiedMethods()
    {
        $method = $this->methods('method3');

        $method1 = Method::findOne(['uid' => $method->uid]);
        $this->assertNotNull($method1);

        Method::deleteExpiredUnverifiedMethods();

        $method2 = Method::findOne(['uid' => $method->uid]);
        $this->assertNull($method2);
    }

}