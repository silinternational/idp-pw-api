<?php

/**
 * Application configuration shared by all applications unit tests
 */

use yii\helpers\ArrayHelper;
use tests\mock\personnel\Component as PersonnelComponent;
use tests\mock\auth\Component as AuthComponent;

$mainConfig = require(__DIR__ . '/../../../common/config/main.php');

$config = [
    'id' => 'unit_tests_app',
    'basePath' => dirname(dirname(dirname(__DIR__))),
];

return ArrayHelper::merge(
    $mainConfig,
    $config
);
