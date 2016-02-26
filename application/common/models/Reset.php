<?php
namespace common\models;

use yii\helpers\ArrayHelper;

use common\helpers\Utils;

/**
 * Class Reset
 * @package common\models
 * @method Reset self::findOne([])
 */
class Reset extends ResetBase
{

    const TYPE_METHOD = 'method';
    const TYPE_SUPERVISOR = 'supervisor';
    const TYPE_SPOUSE = 'spouse';

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

                [
                    ['expires'], 'default', 'value' => Utils::getDatetime(self::getExpireTimestamp()),
                ],

                [
                    ['type'], 'in', 'range' => [self::TYPE_METHOD, self::TYPE_SUPERVISOR, self::TYPE_SPOUSE],
                    'message' => 'Reset type must be either '.self::TYPE_METHOD.' or '.
                        self::TYPE_SUPERVISOR.' or '.self::TYPE_SPOUSE.' .',
                ],
            ],
            parent::rules()
        );
    }

    /**
     * Calculate expiration timestamp based on given timestamp and configured reset lifetime
     * @param null $time
     * @return integer
     */
    public static function getExpireTimestamp($time=null)
    {
        $time = is_null($time) ? time() : $time;

        return $time+\Yii::$app->params['reset']['lifetimeSeconds'];
    }
}