<?php
namespace tests\unit\common\models;

use common\models\Method;
use common\models\User;
use common\models\Reset;
use tests\helpers\EmailUtils;
use yii\codeception\DbTestCase;

use tests\unit\fixtures\common\models\UserFixture;
use tests\unit\fixtures\common\models\MethodFixture;
use tests\unit\fixtures\common\models\ResetFixture;
use yii\web\TooManyRequestsHttpException;

/**
 * Class ResetTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method Method methods($key)
 * @method Reset resets($key)
 */
class ResetTest extends DbTestCase
{
    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
            'methods' => MethodFixture::className(),
            'resets' => ResetFixture::className(),
        ];
    }

    public function testDefaultValues()
    {
        Reset::deleteAll();
        $user1 = $this->users('user1');
        $reset = new Reset();
        $reset->user_id = $user1->id;
        if ( ! $reset->save()) {
            $this->fail('Failed to create Reset: ' . print_r($reset->getFirstErrors(), true));
        }

        $this->assertEquals(32, strlen($reset->uid));
        $this->assertEquals(Reset::TYPE_PRIMARY, $reset->type);
        $this->assertNull($reset->method_id);
        $this->assertNull($reset->code);
        $this->assertEquals(0, $reset->attempts);
        $this->assertNotNull($reset->expires);
        $this->assertNull($reset->disable_until);
        $this->assertNotNull($reset->created);
    }

    public function testGetExpireTimestamp()
    {
        // Set config to consistent value
        \Yii::$app->params['reset']['lifetimeSeconds'] = 100;
        $reset = $this->resets('reset1');
        $time = time();

        $expireTimestamp = $reset->getExpireTimestamp();

        $this->assertEquals($time + 100, $expireTimestamp, null, 2);
    }

    public function testCannotCreateSecondResetForUser()
    {
        $existing = $this->resets('reset1');

        $second = new Reset();
        $second->user_id = $existing->user_id;
        $this->assertFalse($second->save());
    }

    public function testFindOrCreateNew()
    {
        Reset::deleteAll();
        $user = $this->users('user1');

        $reset = Reset::findOrCreate($user);

        $this->assertEquals(32, strlen($reset->uid));
        $this->assertEquals(Reset::TYPE_PRIMARY, $reset->type);
        $this->assertNull($reset->method_id);
        $this->assertNull($reset->code);
        $this->assertEquals(0, $reset->attempts);
        $this->assertNotNull($reset->expires);
        $this->assertNull($reset->disable_until);
        $this->assertNotNull($reset->created);
    }

    public function testFindOrCreateExisting()
    {
        $existing = $this->resets('reset1');
        $new = Reset::findOrCreate($existing->user);

        $this->assertEquals($existing->id, $new->id);
    }

    public function testSendPhone()
    {
        $reset = $this->resets('reset2');
        $this->assertNull($reset->code);
        $reset->send();
        $this->assertEquals('1234', $reset->code);
        $this->assertEquals(1, $reset->attempts);
    }

    public function testIsUserProvidedCodeCorrect()
    {
        $reset = $this->resets('reset2');
        $reset->send();
        $this->assertTrue($reset->isUserProvidedCodeCorrect('1234'));

        $this->assertFalse($reset->isUserProvidedCodeCorrect('1111'));
    }

    public function testSendPrimary()
    {
        EmailUtils::removeEmailFiles();
        $reset = $this->resets('reset1');
        $attempts = $reset->attempts;

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $reset->send();

        $this->assertEquals(1, EmailUtils::getEmailFilesCount());
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($reset->code));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($reset->user->email));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('password change for your'));
        $this->assertEquals($attempts + 1, $reset->attempts);

        $reset->send();

        $this->assertEquals(2, EmailUtils::getEmailFilesCount());
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($reset->code));
        $this->assertEquals($attempts + 2, $reset->attempts);
    }

    public function testSendSupervisorHasSupervisor()
    {
        EmailUtils::removeEmailFiles();
        $reset = $this->resets('reset1');
        $reset->type = Reset::TYPE_SUPERVISOR;
        $attempts = $reset->attempts;

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $reset->send();

        $this->assertEquals(1, EmailUtils::getEmailFilesCount());
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($reset->code));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('supervisor@domain.org'));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('requested a password change for their'));
        $this->assertEquals($attempts + 1, $reset->attempts);
    }

    public function testSendSupervisorNoSupervisor()
    {
        EmailUtils::removeEmailFiles();
        $reset = $this->resets('reset2');
        $reset->type = Reset::TYPE_SUPERVISOR;

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $this->setExpectedException('\Exception', '', 1461173406);
        $reset->send();
        $this->assertEquals(0, EmailUtils::getEmailFilesCount());
    }

    public function testSendSpouseHasSpouse()
    {
        EmailUtils::removeEmailFiles();
        $reset = $this->resets('reset1');
        $reset->type = Reset::TYPE_SPOUSE;
        $attempts = $reset->attempts;

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $reset->send();

        $this->assertEquals(1, EmailUtils::getEmailFilesCount());
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($reset->code));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('spouse@domain.org'));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('requested a password change for their'));
        $this->assertEquals($attempts + 1, $reset->attempts);
    }

    public function testSendSpouseNoSpouse()
    {
        EmailUtils::removeEmailFiles();
        $reset = $this->resets('reset2');
        $reset->type = Reset::TYPE_SPOUSE;

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $this->setExpectedException('\Exception', '', 1461173477);
        $reset->send();
        $this->assertEquals(0, EmailUtils::getEmailFilesCount());
    }

    public function testSendMethodEmail()
    {
        EmailUtils::removeEmailFiles();
        $reset = $this->resets('reset3');
        $attempts = $reset->attempts;

        $this->assertEquals(0, EmailUtils::getEmailFilesCount());

        $reset->send();

        $this->assertEquals(1, EmailUtils::getEmailFilesCount());
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($reset->code));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('email-1456769679@domain.org'));
        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated('password change for your'));
        $this->assertEquals($attempts + 1, $reset->attempts);

    }

    public function testDisableIsDisabled()
    {
        $reset = $this->resets('reset1');
        $this->assertFalse($reset->isDisabled());

        $expireDate = time() + \Yii::$app->params['reset']['disableDuration'];
        $reset->disable();
        $this->assertTrue($reset->isDisabled());
        $this->assertEquals($expireDate, strtotime($reset->disable_until),'',2);
    }

    public function testSetType()
    {
        $reset = $this->resets('reset1');
        $this->assertEquals(Reset::TYPE_PRIMARY, $reset->type);

        $reset->setType(Reset::TYPE_SUPERVISOR);
        $this->assertEquals(Reset::TYPE_SUPERVISOR, $reset->type);

        $reset->setType(Reset::TYPE_SPOUSE);
        $this->assertEquals(Reset::TYPE_SPOUSE, $reset->type);

        $reset->setType(Reset::TYPE_METHOD, 1);
        $this->assertEquals(Reset::TYPE_METHOD, $reset->type);
        $this->assertEquals(1, $reset->method_id);

        $reset->setType(Reset::TYPE_PRIMARY);
        $this->assertEquals(Reset::TYPE_PRIMARY, $reset->type);
        $this->assertNull($reset->method_id);
    }

    public function testTrackAttempt()
    {
        $reset = $this->resets('reset1');
        $this->assertEquals(0, $reset->attempts);

        $reset->trackAttempt('test');
        $this->assertEquals(1, $reset->attempts);

        $reset->trackAttempt('test');
        $this->assertEquals(2, $reset->attempts);

        try {
            for ($i = 0; $i <= \Yii::$app->params['reset']['maxAttempts']; $i++) {
                $reset->trackAttempt('test');
            }
            $this->fail('TooManyRequestsHttpException should have been thrown');
        } catch (TooManyRequestsHttpException $e) {
            
        }

        $this->assertTrue($reset->isDisabled());
    }

}