<?php
namespace tests\unit\common\models;

use common\models\EventLog;
use common\models\User;
use yii\codeception\DbTestCase;

use tests\unit\fixtures\common\models\EventLogFixture;
use tests\unit\fixtures\common\models\MethodFixture;
use tests\unit\fixtures\common\models\UserFixture;

/**
 * Class EventLogTest
 * @package tests\unit\common\models
 * @method User users($key)
 * @method EventLog eventLogs($key)
 */
class EventLogTest extends DbTestCase
{
    public function fixtures()
    {
        return [
            'users' => UserFixture::className(),
            'event_logs' => EventLogFixture::className(),
        ];
    }

    public function testLogNoUser()
    {
        EventLog::log('test-1461183312', 'testing-1461183312');

        /*
         * Check if entry was created
         */
        $eventLog = EventLog::findOne(['topic' => 'test-1461183312']);

        $this->assertNotNull($eventLog);
    }

    public function testLogInvalidUser()
    {
        $this->setExpectedException('\Exception', '', 1461182172);
        EventLog::log('test-1461183456', 'testing-1461183456', 4499999999);
    }

    public function testLogWithUser()
    {
        $user = $this->users('user1');

        $this->assertEquals(0, count($user->eventLogs));

        EventLog::log('test-1461183621', 'testing-1461183621', $user->id);

        /*
         * reload user from db
         */
        $reload = User::findOne(['id' => $user->id]);
        $this->assertEquals(1, count($reload->eventLogs));
        $this->assertEquals('test-1461183621', $reload->eventLogs[0]['topic']);

    }

}