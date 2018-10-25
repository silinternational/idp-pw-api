<?php
namespace common\models;

use yii\helpers\ArrayHelper;

use common\helpers\Utils;
use yii\web\ServerErrorHttpException;

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
                    ['scenario'], 'in', 'range' => [self::SCENARIO_CHANGE, self::SCENARIO_RESET],
                    'message' => 'Scenario must be either ' . self::SCENARIO_CHANGE . ' or ' .
                        self::SCENARIO_RESET . '.',
                ],

                [
                    ['reset_type'], 'in', 'range' => [
                        Reset::TYPE_PRIMARY, Reset::TYPE_METHOD, Reset::TYPE_SUPERVISOR, Reset::TYPE_SPOUSE
                    ],
                    'message' => 'Reset type must be either ' . Reset::TYPE_PRIMARY . ' or ' . Reset::TYPE_METHOD .
                        ' or ' . Reset::TYPE_SUPERVISOR . ' or ' . Reset::TYPE_SPOUSE . ' .',
                    'when' => function() { return $this->scenario === self::SCENARIO_RESET; },
                ],

                [
                    ['method_type'], 'in', 'range' => [Method::TYPE_EMAIL],
                    'message' => 'Method type must be ' . Method::TYPE_EMAIL . '.',
                    'when' => function() { return $this->scenario === self::SCENARIO_RESET; },
                ],
            ],
            parent::rules()
        );
    }

    /**
     * Create log entry of password change
     * @param int $userId
     * @param string $scenario
     * @param string $ipAddress
     * @param string|null $resetType
     * @param string|null $methodType
     * @param string|null $maskedMethodValue
     * @throws ServerErrorHttpException
     */
    public static function log(
        $userId,
        $scenario,
        $ipAddress,
        $resetType = null,
        $methodType = null,
        $maskedMethodValue = null
    ) {
        $log = new PasswordChangeLog();
        $log->user_id = $userId;
        $log->scenario = $scenario;
        $log->ip_address = $ipAddress;
        $log->reset_type = $resetType;
        $log->method_type = $methodType;
        $log->masked_value = $maskedMethodValue;

        if ( ! $log->save()) {
            \Yii::error([
                'action' => 'log password change',
                'status' => 'error',
                'user_id' => $userId,
                'scenario' => $scenario,
                'reset_type' => $resetType,
                'method_type' => $methodType,
                'masked_value' => $maskedMethodValue,
                'error' => $log->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException(
                'Unable to log password change' . print_r($log->getFirstErrors(), true),
                1470246318
            );
        }

    }
}
