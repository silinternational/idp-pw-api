<?php
/**
     * Application configuration shared by all applications unit tests
     */

use yii\helpers\ArrayHelper;
use tests\mock\personnel\Component as PersonnelComponent;

$mainLocalConfig = require(__DIR__ . '/../../../common/config/main.php');

$config = [
    'id' => 'unit_tests_app',
    'basePath' => dirname(dirname(dirname(__DIR__))),
    'components' => [
        'personnel' => [
            'class' => PersonnelComponent::className(),
        ],
    ],
    'params' => [

    ],
];

return ArrayHelper::merge(
    $mainLocalConfig,
    $config
);