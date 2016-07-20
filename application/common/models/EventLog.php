<?php
namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

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
     * @param string $topic
     * @param string|array $details
     * @param integer|null $userId
     * @throws \Exception
     */
    public static function log($topic, $details, $userId = null)
    {
        $eventLog = new EventLog();
        $eventLog->user_id = $userId;
        $eventLog->topic = $topic;
        $eventLog->details = is_array($details) ? Json::encode($details) : $details;

        if ( ! $eventLog->save()) {
            throw new \Exception('Unable to save event log entry', 1461182172);
        }
    }

}