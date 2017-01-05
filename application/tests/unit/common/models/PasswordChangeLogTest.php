<?php
namespace tests\unit\common\models;

use common\models\PasswordChangeLog;
use common\models\User;
use yii\codeception\DbTestCase;

use tests\unit\fixtures\common\models\PasswordChangeLogFixture;
use tests\unit\fixtures\common\models\UserFixture;

/**
 * Class PasswordChangeLogTest
 * @package tests\unit\common\models
 */
class PasswordChangeLogTest extends DbTestCase
{
    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
            'password_change_logs' => PasswordChangeLogFixture::className(),
        ];
    }

    public function testLog()
    {
        /*
         * Make sure there are not records already
         */
        PasswordChangeLog::deleteAll();

        $user = $this->users('user1');

        PasswordChangeLog::log(
            $user->id,
            PasswordChangeLog::SCENARIO_CHANGE,
            '123.123.123.123'
        );

        $entry = PasswordChangeLog::findOne(['user_id' => $user->id]);
        $this->assertEquals('123.123.123.123', $entry->ip_address);
    }

    public function testLogInvalidTypeException()
    {

        $user = $this->users('user1');

        $this->expectException(\yii\web\ServerErrorHttpException::class);
        $this->expectExceptionCode('1470246318');

        PasswordChangeLog::log(
            $user->id,
            'invalid type',
            '123.123.123.123'
        );
    }


}