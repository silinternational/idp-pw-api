<?php
namespace common\models;

use common\helpers\Utils;
use common\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class EmailQueue extends EmailQueueBase
{
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    'attempts_count', 'default', 'value' => 0,
                ],
                [
                    'created', 'default', 'value' => Utils::getDatetime(),
                ],
                [
                    ['to_address', 'cc_address'], 'email',
                ],
                [
                    'event_log_user_id', 'exist', 'targetClass' => User::className(),
                    'targetAttribute' => ['event_log_user_id' => 'id'],
                ],
            ],
            parent::rules()
        );
    }

    /**
     * Attempt to send an email, but on error queue it.
     * Throws exception if send and queue fail
     * @param string $toAddress
     * @param string $subject
     * @param string $textBody
     * @param null|string $htmlBody
     * @param null|string $ccAddress
     * @param null|integer $eventLogUserId
     * @param null|string $eventLogTopic
     * @param null|string $eventLogDetails
     * @return EmailQueue
     * @throws \Exception
     */
    public static function sendOrQueue(
        $toAddress,
        $subject,
        $textBody,
        $htmlBody = null,
        $ccAddress = null,
        $eventLogUserId = null,
        $eventLogTopic = null,
        $eventLogDetails = null
    ) {
        $emailQueue = new EmailQueue();
        $emailQueue->to_address = $toAddress;
        $emailQueue->subject = $subject;
        $emailQueue->text_body = $textBody;
        $emailQueue->html_body = $htmlBody;
        $emailQueue->cc_address = $ccAddress;
        $emailQueue->event_log_user_id = $eventLogUserId;
        $emailQueue->event_log_topic = $eventLogTopic;
        $emailQueue->event_log_details = $eventLogDetails;

        try {
            $emailQueue->send();
        } catch (\Exception $e) {
            /*
             * Send failed, attempt to queue
             */
            $emailQueue->attempts_count += 1;
            $emailQueue->last_attempt = Utils::getDatetime();
            $emailQueue->queue();
        }

        
        return $emailQueue;
    }

    /**
     * Attempt to send email. Returns true on success or throws exception.
     * DOES NOT QUEUE ON FAILURE
     * @return void
     * @throws \Exception
     */
    public function send()
    {
        $log = [
            'class' => __CLASS__,
            'action' => 'send',
            'to' => $this->to_address,
            'subject' => $this->subject,
        ];

        /*
         * Try to send email or throw exception
         */
        try {
            $message = $this->getMessage();
            if ( ! $message->send()) {
                throw new \Exception('Unable to send email', 1461011826);
            }

            /*
             * Create event log entry if needed
             */
            $this->createEventLogEntry();

            /*
             * Remove entry from queue (if saved to queue) after successful send
             */
            $this->removeFromQueue();

            /*
             * Log success
             */
            $log['status'] = 'sent';
            \Yii::warning($log, 'application');

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Attempt to send email and on failure update attempts count and save (queue it)
     * @throws \Exception
     */
    public function retry()
    {
        try {
            $this->send();
        } catch (\Exception $e) {
            /*
             * Send failed, attempt to queue
             */
            $this->attempts_count += 1;
            $this->last_attempt = Utils::getDatetime();

            $log = [
                'class' => __CLASS__,
                'action' => 'retry',
                'to' => $this->to_address,
                'subject' => $this->subject,
                'attempts_count' => $this->attempts_count,
                'last_attempt' => $this->last_attempt,
            ];
            \Yii::error($log);

            $this->queue($log, $e);
        }
    }

    /**
     * Builds a mailer object from $this and returns it
     * @return \yii\mail\MessageInterface
     */
    private function getMessage()
    {
        $mailer = \Yii::$app->mailer->compose();
        $mailer->setFrom(\Yii::$app->params['fromEmail']);
        $mailer->setTo($this->to_address);
        $mailer->setSubject($this->subject);
        $mailer->setTextBody($this->text_body);

        /*
         * Conditionally set optional fields
         */
        $setMethods = [
            'setCc' => $this->cc_address,
            'setHtmlBody' => $this->html_body,
        ];
        foreach ($setMethods as $method => $value) {
            if ($value) {
                $mailer->$method($value);
            }
        }
        
        return $mailer;
    }

    /**
     * Creates an EventLog entry if $this->event_log_* properties are set
     * @return boolean
     * @throws \Exception
     */
    private function createEventLogEntry()
    {
        if ($this->event_log_topic !== null && $this->event_log_details !== null && $this->event_log_user_id !== null) {
            EventLog::log($this->event_log_topic, $this->event_log_details, $this->event_log_user_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Queue's email for sending later, if there is an error saving to db it throws an exception
     * @throws \Exception
     */
    private function queue()
    {
        $log = [
            'class' => __CLASS__,
            'action' => 'queue',
            'to' => $this->to_address,
            'subject' => $this->subject,
            'attempts_count' => $this->attempts_count,
            'last_attempt' => $this->last_attempt,
        ];

        if ( ! $this->save()) {
            /*
             * Queue failed, log it and throw exception
             */
            $log['status'] = 'failed to queue';
            $log['error'] = Json::encode($this->getFirstErrors());
            \Yii::error($log, 'application');
            throw new \Exception('Unable to queue email: ' . $log['error'], 1461009236);
        }
        
        /*
         * Email queued, log it
         */
        $log['status'] = 'queued';
        \Yii::warning($log, 'application');
    }

    /**
     * If $this has been saved to database, it will be deleted and on failure throw an exception
     * @throws \Exception
     */
    private function removeFromQueue()
    {
        try {
            if ($this->id && ! $this->delete()) {
                throw new \Exception(
                    'Unable to delete email queue entry',
                    1461012183
                );
            }
        } catch (\Exception $e) {
            $log = [
                'class' => __CLASS__,
                'action' => 'delete after send',
                'status' => 'failed to delete',
                'error' => $e->getMessage(),
            ];
            \Yii::error($log, 'application');

            throw new \Exception(
                'Unable to delete email queue entry',
                1461012337
            );
        }
    }
    
}
