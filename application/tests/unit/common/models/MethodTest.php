<?php
namespace tests\unit\common\models;

use Sil\Codeception\TestCase\Test;
use common\models\Method;
use common\models\User;
use tests\helpers\EmailUtils;
use tests\unit\fixtures\common\models\MethodFixture;
use tests\unit\fixtures\common\models\UserFixture;
use yii\web\BadRequestHttpException;

/**
 * Class MethodTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method Method methods($key)
 * @property \Codeception\Module\Yii2 tester
 */
class MethodTest extends Test
{
    public function _fixtures()
    {
        return [
            'users' => UserFixture::class,
            'methods' => MethodFixture::class,
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

    public function testGetMaskedValueEmail()
    {
        $method = $this->methods('method2');
        $this->assertEquals('e**************9@d*****.o**', $method->getMaskedValue());
    }

    public function testCreateAndSendVerificationEmail()
    {
        /* Since these tests depend on emails being written to files, don't
         * use the email service for now.  */
        \Yii::$app->params['emailVerification']['useEmailService'] = false;

        $user = $this->users('user1');

        $this->assertEquals(0, EmailUtils::getEmailFilesCount($this->tester));

        $method = Method::createAndSendVerification(
            $user->id,
            Method::TYPE_EMAIL,
            'unique-1461443608@email.com'
        );

        $this->assertEquals(1, EmailUtils::getEmailFilesCount($this->tester));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($this->tester, $method->verification_code));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($this->tester, $method->value));

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

    public function testValidateAndSetAsVerifiedValid()
    {
        $method = $this->methods('method3');

        $method->validateAndSetAsVerified($method->verification_code);

        $this->assertEquals(1, $method->verified);
        $this->assertNull($method->verification_code);
        $this->assertNull($method->verification_expires);
    }

    public function testValidateAndSetAsVerifiedInvalid()
    {
        $method = $this->methods('method4');

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1461442988);
        $method->validateAndSetAsVerified('asdf1234');

        $this->assertEquals(0, $method->verified);
        $this->assertEquals(1, $method->verification_attempts);
        $this->assertNotNull($method->verification_code);
        $this->assertNotNull($method->verification_expires);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1461442988);
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
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionCode(1470169372);
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

}
