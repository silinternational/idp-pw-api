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

        $emailQueue->send();

        return $emailQueue;
    }

    /**
     * Attempt to send email. Returns true on success or throws exception.
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
            $mailer = $this->getMailer();
            $sent = $mailer->send();
            if ($sent === 0 || $sent === false) {
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
            /*
             * Send failed, attempt to queue
             */
            $this->attempts_count += 1;
            $this->last_attempt = Utils::getDatetime();
            $this->queue($log, $e);
        }
    }

    /**
     * Builds a mailer object from $this and returns it
     * @return \yii\mail\MessageInterface
     */
    private function getMailer()
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
     */
    private function createEventLogEntry()
    {
        if ($this->event_log_user_id !== null && $this->event_log_topic !== null && $this->event_log_details !== null) {
//            EventLog::log($this->event_log_user_id, $this->event_log_topic, $this->event_log_details);
        }
    }

    /**
     * Queue's email for sending later, if there is an error saving to db it throws an exception
     * @param array $log Optionally provide an array of data to be logged
     * @param \Exception|null $previousException Optionally provide an exception if one occurred
     *                                           resulting in queueing email
     * @throws \Exception
     */
    private function queue($log = [], \Exception $previousException = null)
    {
        if ( ! $this->save()) {
            /*
             * Queue failed, log it and throw exception
             */
            $log['status'] = 'failed to queue';
            $log['error'] = Json::encode($this->getFirstErrors());
            \Yii::error($log, 'application');
            throw new \Exception('Unable to send or queue email', 1461009236);
        } else {
            /*
             * Email queued, log it
             */
            $log['status'] = 'queued';
            if ($previousException !== null) {
                $log['error'] = $previousException->getMessage();
            }

            \Yii::error($log, 'application');
        }
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
                    'Unable to delete email queue entry after successful send.',
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
                'Unable to delete email queue entry after successful send.',
                1461012337
            );
        }
    }
    
}
