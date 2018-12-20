<?php

use Sil\PhpEnv\Env;
use yii\helpers\ArrayHelper;
use \yii\web\ServerErrorHttpException;

require(__DIR__ . '/../../vendor/autoload.php');

define('YII_ENV', Env::get('APP_ENV', 'prod'));
define('YII_DEBUG', YII_ENV !== 'prod');

require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');

/*
 * Load environment config if present, else expect local.php
 */
$appEnv = Env::get('APP_ENV', 'production');
$validEnvs = ['test', 'development', 'dev', 'staging', 'stage', 'stg', 'production', 'prod', 'prd'];
if (! in_array($appEnv, $validEnvs, true)) {
    throw new ServerErrorHttpException('Invalid APP_ENV provided');
}
$configPath = __DIR__ . '/../../common/config';
if (file_exists($configPath . '/' . $appEnv . '.php')) {
    $envConfig = require $configPath . '/' . $appEnv . '.php';
} elseif (file_exists($configPath . '/local.php')) {
    $envConfig = require $configPath . '/local.php';
}

$config = ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    $envConfig,
    require(__DIR__ . '/../config/main.php')
);

$application = new yii\web\Application($config);
$application->run();
