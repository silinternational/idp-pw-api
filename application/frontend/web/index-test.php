<?php

defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'test');

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require_once(__DIR__ . '/../../common/config/bootstrap.php');
require_once(__DIR__ . '/../config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/local.php'),
    require(__DIR__ . '/../config/main.php')
);

$application = new yii\web\Application($config);
$application->run();
