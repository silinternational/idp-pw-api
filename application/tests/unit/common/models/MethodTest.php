<?php
namespace tests\unit\common\models;

use common\helpers\Utils;
use common\models\Method;
use common\models\User;
use yii\codeception\DbTestCase;

use tests\helpers\EmailUtils;
use tests\unit\fixtures\common\models\UserFixture;
use tests\unit\fixtures\common\models\MethodFixture;
use yii\web\ServerErrorHttpException;

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
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1470169372);
        Method::createAndSendVerification(
            1,
            'invalid type',
            'value'
        );
    }

    public function testCreateAndSendVerificationInvalidEmail()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1461459797);
        Method::createAndSendVerification(
            1,
            Method::TYPE_EMAIL,
            'not-a-email'
        );
    }

    public function testCreateAndSendVerificationInvalidPhone()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1461375342);
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
        $this->assertEquals(1, $method->verification_attempts);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1461442988);
        $method->validateAndSetAsVerified('asdf1234');

        $this->assertEquals(0, $method->verified);
        $this->assertEquals(2, $method->verification_attempts);
        $this->assertNotNull($method->verification_code);
        $this->assertNotNull($method->verification_expires);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1461442988);
        $method->validateAndSetAsVerified('asdf1234');
        $this->assertEquals(3, $method->verification_attempts);
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

    public function testCreateAndSendVerificationSendVerificationFails()
    {
        /*
         * Delete all methods to start clean
         */
        Method::deleteAll();

        /*
         * Attempt to create new method, it should fail when sending verification and
         * delete itself and return an exception
         */
        $this->expectException(ServerErrorHttpException::class);
        $this->expectExceptionCode(1469736442);
        $user = $this->users('user1');
        Method::createAndSendVerification(
            $user->id,
            Method::TYPE_PHONE,
            '14044044044'
        );

        /*
         * Make sure a record for this method does not exist
         */
        $found = Method::findOne(['value' => '14044044044']);
        $this->assertNull($found);
    }

    public function testCreateAndSendVerificationExistingUnverifiedMethodEmail()
    {
        $existing = $this->methods('method4');

        $method = Method::createAndSendVerification($existing->user_id, $existing->type, $existing->value);

        $this->assertEquals($existing->uid, $method->uid);
        $this->assertEquals(1, $method->verification_attempts);

        $method = Method::createAndSendVerification($existing->user_id, $existing->type, $existing->value);

        $this->assertEquals($existing->uid, $method->uid);
        $this->assertEquals(2, $method->verification_attempts);
    }

    public function testCreateAndSendVerificationExistingUnverifiedMethodPhone()
    {
        $existing = $this->methods('method5');

        $method = Method::createAndSendVerification($existing->user_id, $existing->type, $existing->value);

        $this->assertEquals($existing->uid, $method->uid);
        $this->assertEquals(1, $method->verification_attempts);

        $method = Method::createAndSendVerification($existing->user_id, $existing->type, $existing->value);

        $this->assertEquals($existing->uid, $method->uid);
        $this->assertEquals(2, $method->verification_attempts);
    }

}