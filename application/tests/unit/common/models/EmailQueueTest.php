<?php
namespace tests\unit\common\models;

use common\models\EmailQueue;
use tests\helpers\EmailUtils;
use tests\unit\fixtures\common\models\EmailQueueFixture;
use yii\codeception\DbTestCase;
use yii\helpers\FileHelper;

class EmailQueueTest extends DbTestCase
{
    
    public function fixtures()
    {
        return [
            'email_queues' => EmailQueueFixture::className(),
        ];
    }

    public function testDefaults()
    {
        $emailQueue = new EmailQueue();
        $emailQueue->to_address = 'test@test.com';
        $emailQueue->subject = 'test subject';
        $emailQueue->text_body = 'test body';
        $emailQueue->save();

        $this->assertEquals(0, $emailQueue->attempts_count);
        $this->assertNotNull($emailQueue->id);
        $this->assertNotNull($emailQueue->created);
    }

    public function testInvalidEventLogUserId()
    {
        $emailQueue = new EmailQueue();
        $emailQueue->to_address = 'test@test.com';
        $emailQueue->subject = 'test subject';
        $emailQueue->text_body = 'test body';
        $emailQueue->event_log_user_id = 44;

        $this->assertFalse($emailQueue->save());
        $this->assertNotNull($emailQueue->getFirstError('event_log_user_id'));
    }

    public function testInvalidAddresses()
    {
        $emailQueue = new EmailQueue();
        $emailQueue->to_address = 'invalid-address';
        $emailQueue->cc_address = 'invalid-address';
        $emailQueue->subject = 'test subject';
        $emailQueue->text_body = 'test body';

        $this->assertFalse($emailQueue->save());
        $this->assertNotNull($emailQueue->getFirstError('to_address'));
        $this->assertNotNull($emailQueue->getFirstError('cc_address'));
    }

    public function testSendOrQueueSent()
    {
        EmailUtils::removeEmailFiles();
        $data = [
            'toAddress' => 'test@test.com',
            'subject' => 'test subject - 1461087894',
            'text_body' => 'test body',
        ];

        EmailQueue::sendOrQueue(
            $data['toAddress'],
            $data['subject'],
            $data['text_body']
        );

        $this->assertTrue(EmailUtils::hasEmailFileBeenCreated($data['subject']));
    }

    public function testSendOrQueueQueued()
    {
        EmailUtils::removeEmailFiles();
        /*
         * Override mailer config to force attempted connection to fake domain
         */
        \Yii::$app->mailer->useFileTransport = false;
        \Yii::$app->mailer->transport = [
            'class' => 'Swift_SmtpTransport',
            'host' => 'fake.domain.com'
        ];

        $data = [
            'toAddress' => 'test@test.com',
            'subject' => 'test subject - 1461087900',
            'text_body' => 'test body',
        ];

        $emailQueue = EmailQueue::sendOrQueue(
            $data['toAddress'],
            $data['subject'],
            $data['text_body']
        );

        $this->assertNotNull($emailQueue->id);
        $this->assertEquals(1, $emailQueue->attempts_count);

        $this->assertFalse(EmailUtils::hasEmailFileBeenCreated($data['subject']));
    }

    public function testSendAttemptsCountIncreases()
    {
        /*
         * Override mailer config to force attempted connection to fake domain
         */
        \Yii::$app->mailer->useFileTransport = false;
        \Yii::$app->mailer->transport = [
            'class' => 'Swift_SmtpTransport',
            'host' => 'fake.domain.com'
        ];

        $data = [
            'toAddress' => 'test@test.com',
            'subject' => 'test subject - 1461087900',
            'text_body' => 'test body',
        ];

        $emailQueue = EmailQueue::sendOrQueue(
            $data['toAddress'],
            $data['subject'],
            $data['text_body']
        );

        $this->assertNotNull($emailQueue->id);
        $this->assertEquals(1, $emailQueue->attempts_count);

        $emailQueue->send();
        $this->assertEquals(2, $emailQueue->attempts_count);

        $emailQueue->send();
        $this->assertEquals(3, $emailQueue->attempts_count);

    }

    
}