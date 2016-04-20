<?php
namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;

class EventLog extends EventLogBase
{
    /**
     * @return array
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [

                [
                    ['created'], 'default', 'value' => Utils::getDatetime(),
                ],

            ],
            parent::rules()
        );
    }

    /**
     * Create new EventLog entry
     * @param $topic
     * @param $details
     * @param null $userId
     * @throws \Exception
     */
    public static function log($topic, $details, $userId = null)
    {
        $eventLog = new EventLog();
        $eventLog->user_id = $userId;
        $eventLog->topic = $topic;
        $eventLog->details = $details;

        if( ! $eventLog->save()) {
            throw new \Exception('Unable to save event log entry', 1461182172);
        }
    }
}