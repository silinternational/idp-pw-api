<?php
namespace common\models;

use yii\helpers\ArrayHelper;

use common\helpers\Utils;

/**
 * Class PasswordChangeLog
 * @package common\models
 * @method PasswordChangeLog self::findOne([])
 */
class PasswordChangeLog extends PasswordChangeLogBase
{

    const SCENARIO_CHANGE = 'change';
    const SCENARIO_RESET = 'reset';

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['created'], 'default', 'value' => Utils::getDatetime(),
                ],

                [
                    ['scenario'], 'in', 'range' => [self::SCENARIO_CHANGE,self::SCENARIO_RESET],
                    'message' => 'Scenario must be either '.self::SCENARIO_CHANGE.' or '.
                        self::SCENARIO_RESET.'.',
                ],

                [
                    ['reset_type'], 'in', 'range' => [Reset::TYPE_METHOD, Reset::TYPE_SUPERVISOR, Reset::TYPE_SPOUSE],
                    'message' => 'Reset type must be either '.Reset::TYPE_METHOD.' or '.
                        Reset::TYPE_SUPERVISOR.' or '.Reset::TYPE_SPOUSE.' .',
                ],

                [
                    ['method_type'], 'in', 'range' => [Method::TYPE_EMAIL,Method::TYPE_PHONE],
                    'message' => 'Method type must be either '.Method::TYPE_EMAIL.' or '.
                        Method::TYPE_PHONE.'.',
                ],
            ],
            parent::rules()
        );
    }
}