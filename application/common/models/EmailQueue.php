<?php
namespace common\models;

use common\helpers\Utils;
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

            ],
            parent::rules()
        );
    }

    /**
     * Attempt to send an email, but on error queue it.
     * Throws exception if send and queue fail
     * @param string $to
     * @param string $subject
     * @param string $textBody
     * @param null|string $htmlBody
     * @param null|string $cc
     * @param null|integer $eventLogUserId
     * @param null|string $eventLogTopic
     * @param null|string $eventLogDetails
     * @throws \Exception
     */
    public static function sendOrQueue(
        $to,
        $subject,
        $textBody,
        $htmlBody = null,
        $cc = null,
        $eventLogUserId = null,
        $eventLogTopic = null,
        $eventLogDetails = null
    )
    {

        $log = [
            'class' => __CLASS__,
            'action' => 'sendOrQueue',
            'to' => $to,
            'subject' => $subject,
        ];

        $emailQueue = new EmailQueue();
        $emailQueue->to_address = $to;
        $emailQueue->subject = $subject;
        $emailQueue->text_body = $textBody;
        $emailQueue->html_body = $htmlBody;
        $emailQueue->cc_address = $cc;
        $emailQueue->event_log_user_id = $eventLogUserId;
        $emailQueue->event_log_topic = $eventLogTopic;
        $emailQueue->event_log_details = $eventLogDetails;

        try {
            $emailQueue->send();
            $log['status'] = 'sent';
            \Yii::warning($log, 'application');
        } catch (\Exception $e) {
            /*
             * Send failed, attempt to queue
             */
            $emailQueue->attempts_count = 1;
            $emailQueue->last_attempt = Utils::getDatetime();
            $emailQueue->error = $e->getMessage();
            if ( ! $emailQueue->save()) {
                /*
                 * Queue failed, log it and throw exception
                 */
                $log['status'] = 'failed to queue';
                $log['error'] = Json::encode($emailQueue->getFirstErrors());
                \Yii::error($log, 'application');
                throw new \Exception('Unable to send or queue email', 1461009236);
            } else {
                /*
                 * Email queued, log it
                 */
                $log['status'] = 'queued';
                $log['error'] = $e->getMessage();
                \Yii::error($log, 'application');
            }
        }

        /*
         * If event log details are provided, create event log entry
         */
//        if ( $eventLogUserId !== null && $eventLogTopic !== null && $eventLogDetails !== null) {
//            EventLog::log($eventLogUserId, $eventLogTopic, $eventLogDetails);
//        }

    }

    /**
     * Attempt to send email. Returns true on success or throws exception.
     * @return bool
     * @throws \Exception
     */
    public function send()
    {
        $mailer = \Yii::$app->mailer;
        $mail = $mailer->compose();
        $mail->setFrom(\Yii::$app->params['fromEmail']);
        $mail->setTo($this->to_address);
        $mail->setSubject($this->subject);
        $mail->setTextBody($this->text_body);

        /*
         * Conditionally set optional fields
         */
        $setMethods = [
            'setCc' => $this->cc_address,
            'setHtmlBody' => $this->html_body,
        ];
        foreach ($setMethods as $method => $value) {
            if ($value) {
                $mail->$method($value);
            }
        }

        /*
         * Try to send email or throw exception
         */
        $sent = $mail->send();
        if ( $sent === 0 || $sent === false) {
            throw new \Exception('Unable to send email', 1461011826);
        }

        /*
         * Remove entry from queue (if saved to queue) after successful send
         */
        try {
            if ( $this->id && ! $this->delete()) {
                throw new \Exception('Unable to delete email queue entry after successful send.', 1461012183);
            }
        } catch (\Exception $e) {
            \Yii::error([
                'class' => __CLASS__,
                'action' => 'delete after send',
                'status' => 'failed to delete',
                'error' => $e->getMessage(),
            ], 'application');

            throw new \Exception('Unable to delete email queue entry after successful send.', 1461012337);
        }

        return true;
    }
}
