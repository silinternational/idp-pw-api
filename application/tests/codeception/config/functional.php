<?php
/**
 * Application configuration shared by all applications functional tests
 */

use yii\helpers\ArrayHelper;

$mainLocalConfig = require(__DIR__ . '/../../../common/config/main.php');

$config = [
    'id' => 'unit_tests_app',
    'basePath' => dirname(dirname(dirname(__DIR__))),
    'components' => [

    ],
    'params' => [

    ],
];

return ArrayHelper::merge(
    $mainLocalConfig,
    $config
);
