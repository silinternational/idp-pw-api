<?php
namespace tests\unit\common\models;

use common\models\EmailQueue;
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
        $this->removeEmailFiles();
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

        $this->assertTrue($this->hasEmailFileBeenCreated($data['subject']));
    }

    public function testSendOrQueueQueued()
    {
        $this->removeEmailFiles();
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

        $this->assertFalse($this->hasEmailFileBeenCreated($data['subject']));
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

    /**
     * @param string $uniqueContent
     * @return bool
     */
    public function hasEmailFileBeenCreated($uniqueContent)
    {
        $path = $this->getFilesPath();
        if ($path) {
            $files = FileHelper::findFiles($this->getFilesPath());
            foreach ($files as $file) {
                $contents = file_get_contents($file);
                if (substr_count($contents, $uniqueContent) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function removeEmailFiles()
    {
        $path = $this->getFilesPath();
        if ($path) {
            $files = FileHelper::findFiles($this->getFilesPath());
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function getFilesPath()
    {
        return \Yii::getAlias('@runtime/mail');
    }
}