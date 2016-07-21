<?php
namespace console\controllers;

use common\models\EmailQueue;
use yii\console\Controller;

class CronController extends Controller
{
    /**
     * Retry sending emails from queue in small batch sizes
     */
    public function actionSendQueuedEmail()
    {
        try {
            echo "starting cron/send-queued-email" . PHP_EOL;

            $batchSize = \Yii::$app->params['emailQueueBatchSize'];
            $queued = EmailQueue::find()->orderBy(['last_attempt' => SORT_ASC])->limit($batchSize)->all();

            if ($queued === null) {
                echo "no queued emails to send" . PHP_EOL;
                return;
            }

            echo "starting to process " . count($queued) . " queued emails..." . PHP_EOL;

            /** @var EmailQueue $email */
            foreach ($queued as $email) {
                $email->retry();
            }
        } catch (\Exception $e) {
            echo "error occured" . PHP_EOL;
            \Yii::error([
                'action' => 'cron/sendQueuedEmail',
                'status' => 'error',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        echo "done" . PHP_EOL;
    }
}