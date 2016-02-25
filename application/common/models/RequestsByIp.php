<?php
namespace common\models;

use yii\helpers\ArrayHelper;

use common\helpers\Utils;

class RequestsByIp extends RequestsByIpBase
{
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
}