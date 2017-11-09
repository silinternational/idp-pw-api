<?php
defined('YII_DEBUG') || define('YII_DEBUG', false);
defined('YII_ENV') || define('YII_ENV', 'production');

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');

/*
 * Load environment config if present, else expect local.php
 */
$appEnv = \Sil\PhpEnv\Env::get('APP_ENV', 'production');
if ( ! in_array($appEnv, ['development', 'staging', 'production'])) {
    throw new \yii\web\ServerErrorHttpException('Invalid APP_ENV provided');
}
$configPath = __DIR__ . '/../../common/config';
if (file_exists($configPath . '/' . $appEnv . '.php')) {
    $envConfig = require $configPath . '/' . $appEnv . '.php';
} elseif (file_exists($configPath . '/local.php')) {
    $envConfig = require $configPath . '/local.php';
}

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    $envConfig,
    require(__DIR__ . '/../config/main.php')
);

$application = new yii\web\Application($config);
$application->run();
