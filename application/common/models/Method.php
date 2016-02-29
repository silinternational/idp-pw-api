<?php
namespace common\models;

use yii\helpers\ArrayHelper;

use common\helpers\Utils;

/**
 * Class Method
 * @package common\models
 * @method Method self::findOne([])
 */
class Method extends MethodBase
{

    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['uid'], 'default', 'value' => Utils::generateRandomString(),
                ],

                [
                    ['type'], 'in', 'range' => [self::TYPE_EMAIL, self::TYPE_PHONE],
                    'message' => 'Method type must be either ' . self::TYPE_EMAIL . ' or ' . self::TYPE_PHONE . '.',
                ],

                [
                    ['created'], 'default', 'value' => Utils::getDatetime(),
                ],

            ],
            parent::rules()
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMaskedValue()
    {
        if($this->type == self::TYPE_PHONE){
            return Utils::maskPhone($this->value);
        } elseif ($this->type == self::TYPE_EMAIL) {
            return Utils::maskEmail($this->value);
        } else {
            throw new \Exception('Method using invalid Type',1456610497);
        }
    }
}